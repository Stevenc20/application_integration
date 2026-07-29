<?php

namespace App\Console\Commands;

use App\Models\NetworkContainer;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckContainers extends Command
{
    protected $signature = 'network:check-containers';
    protected $description = 'Check Docker container status via Docker Engine API';

    private string $socketPath = '/var/run/docker.sock';

    public function handle(): int
    {
        $containers = $this->fetchContainers();
        if ($containers === null) {
            return Command::SUCCESS;
        }

        $seen = [];

        foreach ($containers as $c) {
            $name = ltrim($c['Names'][0] ?? '', '/');
            $statusText = $c['Status'] ?? '';
            $state = $c['State'] ?? 'unknown';

            $status = match ($state) {
                'running' => 'running',
                'exited', 'dead' => 'stopped',
                'paused' => 'paused',
                default => 'unknown',
            };

            $ports = collect($c['Ports'] ?? [])
                ->map(fn($p) => ($p['IP'] ?? '') ? ($p['IP'] . ':') : '' . ($p['PublicPort'] ?? '') . '->' . ($p['PrivatePort'] ?? '') . '/' . ($p['Type'] ?? ''))
                ->implode(', ');

            preg_match('/(\d+):(\d+):(\d+)/', $statusText, $uptimeMatch);
            $uptimeSec = 0;
            if (!empty($uptimeMatch)) {
                $h = (int)$uptimeMatch[1];
                $m = (int)$uptimeMatch[2];
                $s = (int)$uptimeMatch[3];
                $uptimeSec = $h * 3600 + $m * 60 + $s;
            } elseif (str_contains($statusText, 'Up')) {
                preg_match('/Up\s+(?:About\s+)?(\d+)\s*(hour|minute|day|week)s?/', $statusText, $m2);
                if (!empty($m2)) {
                    $val = (int)$m2[1];
                    $unit = $m2[2] ?? 'minute';
                    $uptimeSec = match ($unit) {
                        'hour' => $val * 3600,
                        'day' => $val * 86400,
                        'week' => $val * 604800,
                        default => $val * 60,
                    };
                }
            }

            $seen[] = $name;

            NetworkContainer::updateOrCreate(
                ['container_name' => $name],
                [
                    'image' => $c['Image'] ?? '',
                    'status' => $status,
                    'ports' => $ports,
                    'uptime_seconds' => $uptimeSec,
                    'last_checked_at' => Carbon::now(),
                ]
            );
        }

        NetworkContainer::whereNotIn('container_name', $seen)
            ->update(['status' => 'removed', 'last_checked_at' => Carbon::now()]);

        $this->info('Checked ' . count($seen) . ' containers');
        return Command::SUCCESS;
    }

    private function fetchContainers(): ?array
    {
        if (!file_exists($this->socketPath)) {
            $this->warn("Socket file not found at {$this->socketPath}");
            return null;
        }

        $transports = stream_get_transports();
        if (!in_array('unix', $transports)) {
            $this->warn('unix transport not supported: ' . implode(',', $transports));
            return null;
        }

        $errno = 0;
        $errstr = '';
        $uri = 'unix://' . $this->socketPath;
        $fp = @stream_socket_client($uri, $errno, $errstr, 5);
        if (!$fp) {
            $this->warn("stream_socket_client failed: [$errno] $errstr");
            return null;
        }

        $req = "GET /containers/json?all=true HTTP/1.1\r\n"
             . "Host: localhost\r\n"
             . "Content-Type: application/json\r\n"
             . "Connection: close\r\n\r\n";

        fwrite($fp, $req);
        $raw = '';
        while (!feof($fp)) {
            $raw .= fgets($fp, 4096);
        }
        fclose($fp);

        $this->info('Raw response length: ' . strlen($raw));
        $this->info('Raw response: ' . substr($raw, 0, 500));

        $parts = explode("\r\n\r\n", $raw, 2);
        $body = $parts[1] ?? '';

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->warn('JSON decode error: ' . json_last_error_msg());
        }
        $this->info('Decoded count: ' . (is_array($decoded) ? count($decoded) : 'not array'));

        return $decoded;
    }
}
