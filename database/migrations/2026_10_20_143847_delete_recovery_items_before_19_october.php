<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Hapus data recovery items tanggal 18 Oktober 2026 kebelakang (sisakan 19 Oktober dan 20 Oktober)
        if (Schema::hasTable('recovery_items')) {
            $oldItemIds = DB::table('recovery_items')
                ->where(function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereNotNull('source_date')->whereDate('source_date', '<', '2026-10-19');
                    })
                    ->orWhere(function ($sub) {
                        $sub->whereNull('source_date')->whereNotNull('original_date')->whereDate('original_date', '<', '2026-10-19');
                    })
                    ->orWhere(function ($sub) {
                        $sub->whereNull('source_date')->whereNull('original_date')->whereDate('created_at', '<', '2026-10-19');
                    });
                })
                ->pluck('id')
                ->toArray();

            if (!empty($oldItemIds)) {
                if (Schema::hasTable('production_plans')) {
                    DB::table('production_plans')
                        ->whereIn('recovery_id', $oldItemIds)
                        ->update([
                            'recovery_id' => null,
                            'source_type' => 'ppc'
                        ]);
                }

                DB::table('recovery_items')->whereIn('id', $oldItemIds)->delete();
            }
        }

        // Bersihkan recovery schedules yang tidak lagi memiliki items
        if (Schema::hasTable('recovery_schedules') && Schema::hasTable('recovery_items')) {
            $orphanSchedules = DB::table('recovery_schedules')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('recovery_items')
                        ->whereColumn('recovery_items.recovery_schedule_id', 'recovery_schedules.id');
                })
                ->pluck('id')
                ->toArray();

            if (!empty($orphanSchedules)) {
                DB::table('recovery_schedules')->whereIn('id', $orphanSchedules)->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data lama uji coba tidak perlu di-restore
    }
};
