<?php

namespace Tests\Feature;

use App\Models\JobMaster;
use App\Models\RepairRejectLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RepairRejectHistoryShiftFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_shift_filter_matches_rows_shown_on_selected_calendar_date(): void
    {
        $user = User::create([
            'name' => 'Supervisor Shift',
            'nrp' => 'SUP-SHIFT-01',
            'password' => bcrypt('secret'),
            'role' => 'superadmin',
        ]);

        $job = JobMaster::create([
            'job_number' => 'JOB-SHIFT-HISTORY',
            'job_name' => 'PART SHIFT HISTORY',
            'line' => 'Line A',
            'target_qty' => 100,
            'status' => 'running',
        ]);

        $this->createLog($job, $user, '2026-11-04 08:00:00', 'Pagi Defect');
        $this->createLog($job, $user, '2026-11-04 22:00:00', 'Malam Defect');
        $this->createLog($job, $user, '2026-11-05 02:00:00', 'Malam After Midnight');
        $this->createLog($job, $user, '2026-11-05 08:00:00', 'Following Morning Defect');

        $pagi = $this->actingAs($user)->get(route('operational.repair_reject.index', [
            'date' => '2026-11-04',
            'shift' => 'pagi',
        ]));

        $pagi->assertOk()
            ->assertSee('Pagi Defect')
            ->assertDontSee('Malam Defect')
            ->assertDontSee('Malam After Midnight')
            ->assertDontSee('Following Morning Defect');

        $malam = $this->actingAs($user)->get(route('operational.repair_reject.index', [
            'date' => '2026-11-04',
            'shift' => 'malam',
        ]));

        $malam->assertOk()
            ->assertSee('Malam Defect')
            ->assertDontSee('Malam After Midnight')
            ->assertDontSee('Pagi Defect')
            ->assertDontSee('Following Morning Defect');

        $allNextDate = $this->actingAs($user)->get(route('operational.repair_reject.index', [
            'date' => '2026-11-05',
        ]));

        $allNextDate->assertOk()
            ->assertSee('Malam After Midnight')
            ->assertSee('Following Morning Defect')
            ->assertDontSee('Pagi Defect')
            ->assertDontSee('Malam Defect');

        $malamNextDate = $this->actingAs($user)->get(route('operational.repair_reject.index', [
            'date' => '2026-11-05',
            'shift' => 'malam',
        ]));

        $malamNextDate->assertOk()
            ->assertSee('Malam After Midnight')
            ->assertDontSee('Following Morning Defect')
            ->assertDontSee('Pagi Defect')
            ->assertDontSee('Malam Defect');

        $pagiNextDate = $this->actingAs($user)->get(route('operational.repair_reject.index', [
            'date' => '2026-11-05',
            'shift' => 'pagi',
        ]));

        $pagiNextDate->assertOk()
            ->assertSee('Following Morning Defect')
            ->assertDontSee('Malam After Midnight')
            ->assertDontSee('Pagi Defect')
            ->assertDontSee('Malam Defect');
    }

    private function createLog(JobMaster $job, User $user, string $createdAt, string $defect): RepairRejectLog
    {
        $log = RepairRejectLog::create([
            'job_master_id' => $job->id,
            'type' => 'repair',
            'defect_name' => $defect,
            'qty_a' => 1,
            'created_by' => $user->id,
        ]);

        DB::table('repair_reject_logs')->where('id', $log->id)->update([
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
        ]);

        return $log->refresh();
    }
}
