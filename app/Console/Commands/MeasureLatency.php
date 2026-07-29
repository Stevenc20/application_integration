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
    protected $description = 'Measure network latency to containers and external targets';

    private array $externalTargets = [
        ['target' => 'Cloudflare DNS (1.1.1.1)', 'host' => '1.1.1.1', 'port' => 53],
        ['target' => 'Google DNS (8.8.8.8)', 'host' => '8.8.8.8', 'port' => 53],
        ['target' => 'Local Gateway', 'host' => '172.17.0.1', 'port' => 80],
    ];

    public function handle(): int
    {
        $now = Carbon::now();
        $source = gethostname() ?: 'laravel-app';

        $containers = NetworkContainer::where('status', 'running')->get();

        $targets = [];
        foreach ($containers as $c) {
            $targets[] = ['target' => $c->container_name, 'host' => $c->container_name, 'type' => 'container', 'port' => 80];
        }
        foreach ($this->externalTargets as $t) {
            $targets[] = $t + ['type' => 'external'];
        }

        $hasPing = $this->pingExists();

        foreach ($targets as $t) {
            $latency = null;
            $status = 'unreachable';

            if ($hasPing) {
                $result = Process::run("ping -c 1 -W 2 " . escapeshellarg($t['host']) . " 2>&1");
                if ($result->successful() && preg_match('/time=(\d+\.?\d*)\s*ms/', $result->output(), $m)) {
                    $latency = (float) $m[1];
                    $status = 'reachable';
                }
            }

            if ($status !== 'reachable') {
                $start = microtime(true);
                $fp = @fsockopen($t['host'], $t['port'] ?? 80, $errno, $errstr, 2);
                if ($fp) {
                    $latency = round((microtime(true) - $start) * 1000, 1);
                    $status = 'reachable';
                    fclose($fp);
                }
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

    private function pingExists(): bool
    {
        $result = Process::run('which ping 2>/dev/null');
        return $result->successful() && trim($result->output()) !== '';
    }
}
