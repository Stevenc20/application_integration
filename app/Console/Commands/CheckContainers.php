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
            $this->warn('Docker socket not available');
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
            return null;
        }

        $errno = 0;
        $errstr = '';
        $fp = @stream_socket_client('unix://' . $this->socketPath, $errno, $errstr, 5);
        if (!$fp) {
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

        $parts = explode("\r\n\r\n", $raw, 2);
        $body = $parts[1] ?? '';

        return json_decode($body, true);
    }
}
