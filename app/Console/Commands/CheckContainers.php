<?php

namespace App\Console\Commands;

use App\Models\NetworkContainer;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class CheckContainers extends Command
{
    protected $signature = 'network:check-containers';
    protected $description = 'Check Docker container status and update network_containers table';

    public function handle(): int
    {
        if (!$this->isDockerAvailable()) {
            $this->warn('Docker not available on this environment');
            return Command::SUCCESS;
        }

        $raw = Process::run('docker ps -a --format "{{.Names}}|{{.Image}}|{{.Status}}|{{.Ports}}"');
        if ($raw->failed()) {
            $this->error('Failed to run docker ps: ' . $raw->errorOutput());
            return Command::FAILURE;
        }

        $lines = array_filter(explode("\n", trim($raw->output())));
        $seen = [];

        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) < 3) continue;

            $name = $parts[0];
            $image = $parts[1] ?? '';
            $statusText = $parts[2] ?? '';

            $status = str_starts_with($statusText, 'Up') ? 'running' :
                     (str_contains($statusText, 'Exited') ? 'stopped' :
                     (str_contains($statusText, 'Paused') ? 'paused' : 'unknown'));

            preg_match('/(\d+):(\d+):(\d+)/', $statusText, $uptimeMatch);
            $uptimeSec = 0;
            if (!empty($uptimeMatch)) {
                $h = (int)($uptimeMatch[1] ?? 0);
                $m = (int)($uptimeMatch[2] ?? 0);
                $s = (int)($uptimeMatch[3] ?? 0);
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

            $ports = $parts[3] ?? '';

            $seen[] = $name;

            NetworkContainer::updateOrCreate(
                ['container_name' => $name],
                [
                    'image' => $image,
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

    private function isDockerAvailable(): bool
    {
        $result = Process::run('docker info --format "{{.OSType}}"');
        return $result->successful() && trim($result->output()) === 'linux';
    }
}
