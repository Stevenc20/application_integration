<?php

namespace Database\Seeders;

use App\Models\Downtime;
use App\Models\JobMaster;
use App\Models\RepairRejectLog;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DataMiningDemoSeeder extends Seeder
{
    private array $jobMasters = [
        ['job_number' => 'JOB-2026-001', 'job_name' => 'Bracket LH', 'line' => 'Line A'],
        ['job_number' => 'JOB-2026-002', 'job_name' => 'Bracket RH', 'line' => 'Line A'],
        ['job_number' => 'JOB-2026-003', 'job_name' => 'Panel Dashboard', 'line' => 'Line B'],
        ['job_number' => 'JOB-2026-004', 'job_name' => 'Support Frame', 'line' => 'Line B'],
        ['job_number' => 'JOB-2026-005', 'job_name' => 'Cover Assy', 'line' => 'Line C'],
        ['job_number' => 'JOB-2026-006', 'job_name' => 'Base Plate', 'line' => 'Line C'],
        ['job_number' => 'JOB-2026-007', 'job_name' => 'Hinge LH', 'line' => 'Line A'],
        ['job_number' => 'JOB-2026-008', 'job_name' => 'Hinge RH', 'line' => 'Line A'],
    ];

    private array $downtimeCategories = [
        'Setting Dies', 'Trouble Mesin', 'Material NG', 'Tunggu Dies',
        'Perbaikan Dies', 'Listrik Padam', 'Meeting', 'Cleaning',
        'Problem Press', 'Change Dies',
    ];

    private array $defectNames = [
        'Scratch', 'Dent', 'Burry', 'Short Mould', 'Porosity',
        'Crack', 'Flash', 'Misalignment', 'Contamination', 'Color Off',
    ];

    public function run(): void
    {
        $now = Carbon::now();

        $jobs = [];
        foreach ($this->jobMasters as $jm) {
            $jobs[] = JobMaster::firstOrCreate(
                ['job_number' => $jm['job_number']],
                $jm
            );
        }

        $this->seedDowntimes($jobs, $now);
        $this->seedRepairRejects($jobs, $now);

        $this->command->info('Seeded ' . count($jobs) . ' job masters');
        $this->command->info('Seeded ' . Downtime::count() . ' downtimes');
        $this->command->info('Seeded ' . RepairRejectLog::count() . ' repair/reject logs');
    }

    private function seedDowntimes(array $jobs, Carbon $now): void
    {
        Downtime::truncate();

        $downtimeWeights = [
            'Setting Dies' => 25, 'Trouble Mesin' => 20, 'Material NG' => 15,
            'Tunggu Dies' => 10, 'Perbaikan Dies' => 10, 'Listrik Padam' => 5,
            'Meeting' => 5, 'Cleaning' => 4, 'Problem Press' => 4, 'Change Dies' => 2,
        ];

        for ($day = 90; $day >= 1; $day--) {
            $date = $now->copy()->subDays($day);
            if ($date->isSunday()) continue;

            foreach (['Pagi', 'Siang', 'Malam'] as $shift) {
                if (mt_rand(1, 10) > 4) continue;

                $job = $jobs[array_rand($jobs)];
                $category = $this->weightedRandom($downtimeWeights);

                $min = match ($category) {
                    'Setting Dies' => 30, 'Trouble Mesin' => 15, 'Material NG' => 10,
                    'Tunggu Dies' => 20, 'Perbaikan Dies' => 20, 'Listrik Padam' => 5,
                    'Meeting' => 15, 'Cleaning' => 10, 'Problem Press' => 10, 'Change Dies' => 15,
                    default => 10,
                };
                $max = match ($category) {
                    'Setting Dies' => 120, 'Trouble Mesin' => 90, 'Material NG' => 60,
                    'Tunggu Dies' => 90, 'Perbaikan Dies' => 60, 'Listrik Padam' => 30,
                    'Meeting' => 30, 'Cleaning' => 20, 'Problem Press' => 45, 'Change Dies' => 45,
                    default => 30,
                };
                $duration = mt_rand($min, $max) * 60;

                $hour = match ($shift) {
                    'Pagi' => mt_rand(7, 14), 'Siang' => mt_rand(14, 18), 'Malam' => mt_rand(20, 23),
                };
                $start = $date->copy()->setTime($hour, mt_rand(0, 59), 0);

                Downtime::create([
                    'job_master_id' => $job->id,
                    'jenis_downtime' => $category,
                    'problem' => match ($category) {
                        'Setting Dies' => 'Setting dies tidak sesuai drawing',
                        'Trouble Mesin' => 'Mesin error pada saat running',
                        'Material NG' => 'Material tidak sesuai spec',
                        'Tunggu Dies' => 'Menunggu dies dari tooling',
                        'Perbaikan Dies' => 'Dies mengalami kerusakan',
                        'Listrik Padam' => 'Listrik PLN padam',
                        'Meeting' => 'Briefing pagi / safety talk',
                        'Cleaning' => 'Cleaning mesin shift change',
                        'Problem Press' => 'Tekanan press tidak stabil',
                        'Change Dies' => 'Change over dies',
                        default => 'Downtime tidak terencana',
                    },
                    'start_time' => $start->format('Y-m-d H:i:s'),
                    'finish_time' => $start->copy()->addSeconds($duration)->format('Y-m-d H:i:s'),
                    'duration_seconds' => $duration,
                    'pic' => collect(['Operator A', 'Operator B', 'Teknisi', 'Setup Man', 'Foreman'])->random(),
                ]);
            }
        }
    }

    private function seedRepairRejects(array $jobs, Carbon $now): void
    {
        RepairRejectLog::truncate();

        $defectWeights = [
            'Scratch' => 20, 'Dent' => 15, 'Burry' => 15, 'Short Mould' => 12,
            'Porosity' => 10, 'Crack' => 10, 'Flash' => 8, 'Misalignment' => 5,
            'Contamination' => 3, 'Color Off' => 2,
        ];

        for ($day = 90; $day >= 1; $day--) {
            $date = $now->copy()->subDays($day);
            if ($date->isSunday()) continue;

            $events = mt_rand(0, 5);
            for ($e = 0; $e < $events; $e++) {
                $job = $jobs[array_rand($jobs)];
                $type = mt_rand(1, 10) > 7 ? 'repair' : 'reject';
                $defect = $this->weightedRandom($defectWeights);
                $qty = mt_rand(1, 20);

                RepairRejectLog::create([
                    'job_master_id' => $job->id,
                    'type' => $type,
                    'defect_name' => $defect,
                    'qty_a' => $qty,
                    'qty_b' => mt_rand(0, 5),
                    'root_cause' => match ($defect) {
                        'Scratch' => 'Material handling tidak sesuai SOP',
                        'Dent' => 'Dies ada kontaminasi',
                        'Burry' => 'Pisau dies sudah tumpul',
                        'Short Mould' => 'Setting clearance kurang tepat',
                        'Porosity' => 'Material mengandung impurity',
                        'Crack' => 'Bending radius terlalu kecil',
                        'Flash' => 'Clamping pressure tidak cukup',
                        'Misalignment' => 'Guide pin aus',
                        'Contamination' => 'Area produksi tidak clean room',
                        'Color Off' => 'Parameter temperatur tidak stabil',
                        default => 'Tidak teridentifikasi',
                    },
                    'countermeasure' => 'Inspeksi dan perbaikan dies / adjustment mesin',
                    'created_by' => 1,
                    'created_at' => $date->setTime(mt_rand(7, 23), mt_rand(0, 59), 0),
                    'updated_at' => $date,
                ]);
            }
        }
    }

    private function weightedRandom(array $weights): string
    {
        $total = array_sum($weights);
        $rand = mt_rand(1, $total);
        foreach ($weights as $key => $weight) {
            $rand -= $weight;
            if ($rand <= 0) return $key;
        }
        return array_key_first($weights);
    }
}
