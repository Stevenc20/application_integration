<?php

namespace App\Console\Commands;

use App\Models\NetworkPortScan;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NetworkScanPorts extends Command
{
    protected $signature = 'network:scan-ports';
    protected $description = 'Scan common ports on Docker containers and gateway';

    private array $targets = [];

    private array $commonPorts = [
        22 => 'SSH', 80 => 'HTTP', 443 => 'HTTPS',
        3306 => 'MySQL', 5432 => 'PostgreSQL', 6379 => 'Redis',
        8080 => 'Alt-HTTP', 8001 => 'Python API',
    ];

    public function handle(): int
    {
        $this->discoverTargets();
        $now = Carbon::now();

        foreach ($this->targets as $target) {
            foreach ($this->commonPorts as $port => $service) {
                $this->scanPort($target, $port, $service, $now);
            }
        }

        NetworkPortScan::where('scanned_at', '<', $now)->delete();
        $this->info('Port scan completed: ' . count($this->targets) . ' targets × ' . count($this->commonPorts) . ' ports');

        return Command::SUCCESS;
    }

    private function discoverTargets(): void
    {
        $this->targets = ['172.17.0.1'];

        try {
            $containers = \App\Models\NetworkContainer::where('status', 'running')->pluck('container_name');
            foreach ($containers as $name) {
                $this->targets[] = $name;
            }
        } catch (\Exception $e) {
            $this->warn('Could not fetch containers: ' . $e->getMessage());
        }
    }

    private function scanPort(string $target, int $port, string $service, Carbon $now): void
    {
        $start = microtime(true);

        $connection = @fsockopen($target, $port, $errno, $errstr, 2);

        $elapsed = (microtime(true) - $start) * 1000;

        $status = $connection ? 'open' : 'closed';

        if ($connection) {
            fclose($connection);
        }

        NetworkPortScan::create([
            'target' => $target,
            'port' => $port,
            'protocol' => 'tcp',
            'service_name' => $service,
            'status' => $status,
            'response_time_ms' => round($elapsed, 1),
            'scanned_at' => $now,
        ]);
    }
}
