<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Downtime;
use App\Models\HambatanJalur;

class BackfillHambatanJalur extends Command
{
    protected $signature = 'hambatan:backfill';
    protected $description = 'Backfill hambatan_jalur records from existing downtimes';

    private $map = [
        'mesin'    => 'MT',
        'dies'     => 'DT',
        'material' => 'MST',
        'logistic' => 'LOGT',
        'produksi' => 'Prot',
    ];

    public function handle()
    {
        $mappedTypes = array_keys($this->map);
        $hjDowntimeIds = HambatanJalur::pluck('downtime_id')->toArray();
        $downtimes = Downtime::whereIn('jenis_downtime', $mappedTypes)
            ->whereNotIn('id', $hjDowntimeIds)
            ->get();

        $count = 0;
        foreach ($downtimes as $downtime) {
            $exists = HambatanJalur::where('downtime_id', $downtime->id)->exists();
            if ($exists) continue;

            $jenis = $this->map[strtolower($downtime->jenis_downtime)] ?? null;
            if (!$jenis) continue;

            $jobMaster = $downtime->jobMaster;
            HambatanJalur::create([
                'downtime_id'     => $downtime->id,
                'line_name'       => $jobMaster->line ?? null,
                'mesin'           => $jobMaster->line ?? null,
                'job_no'          => $jobMaster->job_number ?? null,
                'nama_part'       => $jobMaster->job_name ?? null,
                'jenis_hambatan'  => $jenis,
                'sub_jenis'       => null,
                'problem'         => $downtime->problem,
                'penyebab'        => $downtime->penyebab,
                'penanggulangan'  => $downtime->action,
                'pic_hambatan'    => $downtime->pic,
                'waktu'           => $downtime->start_time,
                'status'          => 'open',
            ]);
            $count++;
        }

        $this->info("Backfilled {$count} hambatan_jalur records.");
    }
}
