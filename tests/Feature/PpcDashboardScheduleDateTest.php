<?php

use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ppc dashboard prioritizes today schedule when today has schedule even if future schedule exists', function () {
    $line = LineMaster::create([
        'line_code' => 'PA',
        'line_name' => 'PRESS A',
        'status' => 'active',
    ]);

    $user = User::create([
        'name' => 'PPC Dashboard Tester',
        'nrp' => 'PPC-DASH-01',
        'password' => 'secret',
        'role' => 'superadmin',
    ]);

    $today = Carbon::today()->toDateString();
    $tomorrow = Carbon::tomorrow()->toDateString();

    // Buat schedule hari ini
    ProductionPlan::create([
        'line_master_id' => $line->id,
        'plan_date' => $today,
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PRESS A',
        'job_master' => 'JOB TODAY',
        'job_no' => 'TODAY-01',
        'row_no' => 1,
        'row_type' => 'job',
        'plan' => 500,
        'status' => 'pending',
    ]);

    // Buat schedule besok
    ProductionPlan::create([
        'line_master_id' => $line->id,
        'plan_date' => $tomorrow,
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PRESS A',
        'job_master' => 'JOB TOMORROW',
        'job_no' => 'TOMORROW-01',
        'row_no' => 1,
        'row_type' => 'job',
        'plan' => 800,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->get(route('ppc.dashboard'));

    $response->assertOk();
    // Tidak boleh menampilkan banner "Belum ada schedule untuk hari ini"
    $response->assertDontSee('Belum ada schedule untuk hari ini', false);
    // Active date harus hari ini
    $response->assertViewHas('activeDate', $today);
    $response->assertViewHas('isTodayData', true);
});

test('ppc dashboard falls back to latest plan date only when today has no schedule', function () {
    $line = LineMaster::create([
        'line_code' => 'PA',
        'line_name' => 'PRESS A',
        'status' => 'active',
    ]);

    $user = User::create([
        'name' => 'PPC Dashboard Tester',
        'nrp' => 'PPC-DASH-02',
        'password' => 'secret',
        'role' => 'superadmin',
    ]);

    $tomorrow = Carbon::tomorrow()->toDateString();

    // Hanya buat schedule besok (hari ini kosong)
    ProductionPlan::create([
        'line_master_id' => $line->id,
        'plan_date' => $tomorrow,
        'shift_name' => 'Shift Pagi',
        'press_name' => 'PRESS A',
        'job_master' => 'JOB TOMORROW',
        'job_no' => 'TOMORROW-01',
        'row_no' => 1,
        'row_type' => 'job',
        'plan' => 800,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->get(route('ppc.dashboard'));

    $response->assertOk();
    // Harus menampilkan banner warning karena hari ini memang belum ada schedule
    $response->assertSee('Belum ada schedule untuk hari ini', false);
    $response->assertViewHas('activeDate', $tomorrow);
    $response->assertViewHas('isTodayData', false);
});
