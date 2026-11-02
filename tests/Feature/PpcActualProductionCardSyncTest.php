<?php

use App\Models\DailyProduction;
use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ppc production plan actual cards show 0 reject when production has not started or has 0 reject', function () {
    $line = LineMaster::create([
        'line_code' => 'PA',
        'line_name' => 'PRESS A',
        'status' => 'active',
    ]);

    $user = User::create([
        'name' => 'PPC Tester',
        'nrp' => 'PPC-001',
        'password' => 'secret',
        'role' => 'superadmin',
    ]);

    $date = '2026-11-02';

    // Buat job plan dengan parameter alokasi mesin a1-a4 (misal total = 1920)
    $plan1 = ProductionPlan::create([
        'line_master_id' => $line->id,
        'plan_date' => $date,
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PRESS A',
        'job_master' => 'JOB PART 01',
        'job_no' => 'JP-01',
        'row_no' => 1,
        'row_type' => 'job',
        'plan' => 5000,
        'ok' => 0,
        'repair' => 0,
        'reject' => 0,
        'a1' => 480,
        'a2' => 480,
        'a3' => 480,
        'a4' => 480, // Total a1..a4 = 1920
        'status' => 'pending',
    ]);

    // Total finish row terimport dari excel dengan parameter a1..a4 = 1920
    $totalRow = ProductionPlan::create([
        'line_master_id' => $line->id,
        'plan_date' => $date,
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PRESS A',
        'job_master' => 'TOTAL FINISH',
        'job_no' => 'TOTAL FINISH',
        'row_no' => 99,
        'row_type' => 'total_finish',
        'plan' => 5000,
        'ok' => 0,
        'repair' => 0,
        'reject' => 0,
        'a1' => 480,
        'a2' => 480,
        'a3' => 480,
        'a4' => 480, // Sebelumnya keliru dihitung sebagai total reject = 1920!
        'status' => 'pending',
    ]);

    // Request ke PPC Production Plan sebelum ada aktivitas produksi
    $response = $this->actingAs($user)->get(route('ppc.planning.production_plan', [
        'date' => $date,
        'press' => 'PRESS A',
        'shift' => 'Shift Pagi',
    ]));

    $response->assertOk();
    // Total Reject card harus bernilai 0 pcs, BUKAN 1,920 pcs
    $response->assertSee('Total Reject', false);
    $response->assertSee('<span class="text-xl font-black text-rose-600">0</span>', false);
    $response->assertDontSee('<span class="text-xl font-black text-rose-600">1,920</span>', false);
});

test('ppc production plan actual cards sync real actual ok, repair, reject from input harian / daily production', function () {
    $line = LineMaster::create([
        'line_code' => 'PA',
        'line_name' => 'PRESS A',
        'status' => 'active',
    ]);

    $user = User::create([
        'name' => 'PPC Tester',
        'nrp' => 'PPC-002',
        'password' => 'secret',
        'role' => 'superadmin',
    ]);

    $date = '2026-11-02';

    $plan = ProductionPlan::create([
        'line_master_id' => $line->id,
        'plan_date' => $date,
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PRESS A',
        'job_master' => 'JOB PART 02',
        'job_no' => 'JP-02',
        'row_no' => 1,
        'row_type' => 'job',
        'plan' => 2000,
        'ok' => 0,
        'repair' => 0,
        'reject' => 0,
        'a1' => 500,
        'a2' => 500,
        'status' => 'running',
    ]);

    // Buat JobMaster yang terhubung dengan plan (format job_number diakhiri -{plan->id})
    $job = JobMaster::create([
        'job_number' => 'JP-02-'.$plan->id,
        'job_name' => 'JOB PART 02',
        'line' => 'PRESS A',
        'status' => 'running',
        'target_qty' => 2000,
    ]);

    // Input Harian mencatat DailyProduction dengan actual_ok = 1500, actual_repair = 30, actual_reject = 15
    DailyProduction::create([
        'job_master_id' => $job->id,
        'work_date' => $date,
        'actual_qty' => 1545,
        'actual_ok' => 1500,
        'actual_repair' => 30,
        'actual_reject' => 15,
        'repair_qty' => 30,
        'reject_qty' => 15,
    ]);

    $response = $this->actingAs($user)->get(route('ppc.planning.production_plan', [
        'date' => $date,
        'press' => 'PRESS A',
        'shift' => 'Shift Pagi',
    ]));

    $response->assertOk();
    $response->assertSee('<span class="text-xl font-black text-emerald-600">1,500</span>', false);
    $response->assertSee('<span class="text-xl font-black text-indigo-600">30</span>', false);
    $response->assertSee('<span class="text-xl font-black text-rose-600">15</span>', false);
});
