<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RecoveryItem;
use App\Models\RecoverySchedule;
use App\Models\ProductionPlan;
use Illuminate\Support\Facades\DB;

class DeleteOldRecoveryItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recovery:clean-before-19';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus data recovery items tanggal 18 Oktober 2026 kebelakang (sisakan 19 Oktober dan seterusnya)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Memulai pembersihan data recovery sebelum 19 Oktober 2026...");

        DB::beginTransaction();
        try {
            // Ambil semua RecoveryItem yang source_date < 2026-10-19 atau original_date < 2026-10-19 atau created_at < 2026-10-19 00:00:00
            // Pastikan data tanggal 19 dan 20 Oktober 2026 tidak terhapus
            $query = RecoveryItem::where(function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNotNull('source_date')->whereDate('source_date', '<', '2026-10-19');
                })
                ->orWhere(function ($sub) {
                    $sub->whereNull('source_date')->whereNotNull('original_date')->whereDate('original_date', '<', '2026-10-19');
                })
                ->orWhere(function ($sub) {
                    $sub->whereNull('source_date')->whereNull('original_date')->whereDate('created_at', '<', '2026-10-19');
                });
            });

            $count = $query->count();
            $itemIds = (clone $query)->pluck('id')->toArray();

            if ($count === 0) {
                $this->info("Tidak ada data recovery lama sebelum 19 Oktober 2026.");
                DB::commit();
                return 0;
            }

            $this->info("Ditemukan {$count} item recovery sebelum 19 Oktober 2026.");

            // Lepaskan referensi recovery_id di production_plans jika ada
            if (!empty($itemIds)) {
                ProductionPlan::whereIn('recovery_id', $itemIds)->update([
                    'recovery_id' => null,
                    'source_type' => 'ppc',
                ]);
            }

            // Hapus recovery items
            $deleted = $query->delete();
            $this->info("Berhasil menghapus {$deleted} data recovery_items.");

            // Bersihkan recovery schedules yang sudah kosong
            $emptySchedules = RecoverySchedule::doesntHave('items')->delete();
            $this->info("Berhasil membersihkan {$emptySchedules} recovery_schedules yang kosong.");

            DB::commit();
            $this->info("Selesai! Data recovery sebelum 19 Oktober 2026 telah dibersihkan.");
            return 0;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Gagal membersihkan data recovery: " . $e->getMessage());
            return 1;
        }
    }
}
