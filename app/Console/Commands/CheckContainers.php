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

            if (!$this->isProjectContainer($c, $name)) {
                continue;
            }

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

        // PURGE NON-PROJECT CONTAINERS: Delete any container not belonging to application_integration
        NetworkContainer::whereNotIn('container_name', $seen)->delete();

        $this->info('Checked ' . count($seen) . ' project containers');
        return Command::SUCCESS;
    }

    private function isProjectContainer(array $c, string $name): bool
    {
        $labels = $c['Labels'] ?? [];
        $composeProject = strtolower($labels['com.docker.compose.project'] ?? '');
        $targetProject = strtolower(env('DOCKER_PROJECT_NAME', 'application_integration'));

        if ($composeProject && (str_contains($composeProject, 'application_integration') || str_contains($composeProject, 'application-integration') || $composeProject === $targetProject)) {
            return true;
        }

        $lowerName = strtolower($name);
        if (str_contains($lowerName, 'application_integration') || str_contains($lowerName, 'application-integration')) {
            return true;
        }

        $workingDir = strtolower($labels['com.docker.compose.project.working_dir'] ?? '');
        if ($workingDir && (str_contains($workingDir, 'application_integration') || str_contains($workingDir, 'application-integration'))) {
            return true;
        }

        if ($targetProject !== 'application_integration' && str_contains($lowerName, $targetProject)) {
            return true;
        }

        if (preg_match('/^(application_integration|app|nginx|db|python|redis|web)[_-]/i', $name) && (empty($composeProject) || $composeProject === $targetProject)) {
            return true;
        }

        return false;
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

        $req = "GET /containers/json?all=true HTTP/1.0\r\n"
             . "Host: localhost\r\n"
             . "Content-Type: application/json\r\n\r\n";

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
