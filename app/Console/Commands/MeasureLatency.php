<?php

namespace App\Console\Commands;

use App\Models\NetworkContainer;
use App\Models\NetworkLatency;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class MeasureLatency extends Command
{
    protected $signature = 'network:measure-latency';
    protected $description = 'Ping Docker containers and measure network latency';

    private array $externalTargets = [
        ['target' => 'Cloudflare DNS (1.1.1.1)', 'host' => '1.1.1.1'],
        ['target' => 'Google DNS (8.8.8.8)', 'host' => '8.8.8.8'],
        ['target' => 'Local Gateway', 'host' => '172.17.0.1'],
    ];

    public function handle(): int
    {
        $now = Carbon::now();
        $source = gethostname() ?: 'laravel-app';

        $containers = NetworkContainer::where('status', 'running')->get();

        $targets = [];
        foreach ($containers as $c) {
            $targets[] = ['target' => $c->container_name, 'host' => $c->container_name, 'type' => 'container'];
        }
        foreach ($this->externalTargets as $t) {
            $targets[] = ['target' => $t['target'], 'host' => $t['host'], 'type' => 'external'];
        }

        foreach ($targets as $t) {
            $result = Process::run("ping -c 1 -W 2 " . escapeshellarg($t['host']) . " 2>&1");

            if ($result->successful() && preg_match('/time=(\d+\.?\d*)\s*ms/', $result->output(), $m)) {
                $latency = (float) $m[1];
                $status = 'reachable';
            } else {
                $latency = null;
                $status = $result->exitCode() === 0 ? 'reachable' : 'unreachable';
            }

            NetworkLatency::create([
                'source' => $source,
                'target' => $t['target'],
                'target_type' => $t['type'],
                'latency_ms' => $latency,
                'status' => $status,
                'measured_at' => $now,
            ]);
        }

        $this->info('Measured latency for ' . count($targets) . ' targets');
        return Command::SUCCESS;
    }
}
