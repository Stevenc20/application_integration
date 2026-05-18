<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MonitoringController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\Admin\QualityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Supervisor\DashboardController as SupervisorDashboard;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Operator\OperatorDashboardController;
use App\Http\Controllers\Admin\JobController;
Use App\Http\Controllers\Operational\InputHarianController;
use App\Http\Controllers\Operational\DandoriController;
use App\Http\Controllers\Planning\ProductionPlanController;
use App\Http\Controllers\Planning\ProductionLineController;

// tessting create user

/* how to create? 
1. php artisan migrate:fresh --seed
2. http://localhost:8000/generate-user

| 
v
*/


// // testing doang

// use App\Models\User;
// use Illuminate\Support\Facades\Hash;

// Route::get('/generate-user', function () {

//     $users = [

//         // ================= ADMIN =================
//         // [
//         //     'name' => 'Admin Utama',
//         //     'nip' => 'ADM001',
//         //     'role' => 'admin',
//         // ],

//         // // ================= SUPERVISOR =================
//         // [
//         //     'name' => 'Supervisor Produksi',
//         //     'nip' => 'SPV001',
//         //     'role' => 'supervisor',
//         // ],

//         // // ================= FOREMAN =================
//         [
//             'name' => 'Foreman Line 1',
//             'nip' => 'FRM001',
//             'role' => 'foreman',
//         ],

//         // ================= OPERATOR =================
//         // [
//         //     'name' => 'Operator Line 1',
//         //     'nip' => 'OPR001',
//         //     'role' => 'operator',
//         // ],
//         // [
//         //     'name' => 'Operator Line 2',
//         //     'nip' => 'OPR002',
//         //     'role' => 'operator',
//         // ],

//         // ================= PPLC =================
//         [
//             'name' => 'PPLC Planner',
//             'nip' => 'PPC001',
//             'role' => 'pplc',
//         ],

//         // ================= QUALITY =================
//         // [
//         //     'name' => 'Quality Control',
//         //     'nip' => 'QC001',
//         //     'role' => 'quality',
//         // ],

//         // ================= PRODUCTION =================
//         // [
//         //     'name' => 'Production Staff',
//         //     'nip' => 'PRD001',
//         //     'role' => 'production',
//         // ],

//     ];

//     $result = [];

//     foreach ($users as $u) {

//         $user = User::updateOrCreate(
//             ['nip' => $u['nip']],
//             [
//                 'name' => $u['name'],
//                 'password' => Hash::make('123456'),
//                 'role' => $u['role'],
//                 'is_active' => 1
//             ]
//         );

//         $result[] = [
//             'name' => $user->name,
//             'nip' => $user->nip,
//             'role' => $user->role,
//             'password' => '123456'
//         ];
//     }

//     return response()->json([
//         'status' => 'success',
//         'total_user' => count($result),
//         'data' => $result
//     ]);

// });


// Route::get('/', function () {
//     return view('welcome', ['name' => 'steven']);
// });




// ADMIN
Route::middleware(['auth','role:admin,supervisor,ppc'])->prefix('admin')->group(function(){

    Route::get('/dashboard', [AdminDashboardController::class,'index'])
        ->name('admin.dashboard');

    Route::resource('/users', UserController::class);

});

// master
Route::middleware(['auth','role:admin,ppc,supervisor'])->prefix('master/job')->group(function () {

    Route::get('/', [JobController::class, 'index'])->name('master.job');

    Route::post('/store', [JobController::class, 'store'])->name('master.job.store');

    Route::put('/update/{id}', [JobController::class, 'update']);

    Route::get('/delete/{id}', [JobController::class, 'delete']);

    Route::get('/edit/{id}', [JobController::class, 'edit']);

});

// SHARED SUPERVISOR ROUTES FOR DASHBOARDS, DOWNTIME, AND REPORTS
Route::middleware(['auth','role:supervisor,ppc,operator,foreman'])
->prefix('supervisor')
->name('supervisor.')
->group(function(){

    Route::get('/dashboard', [SupervisorDashboard::class, 'index'])->name('dashboard');
    Route::get('/dashboard/api-data', [SupervisorDashboard::class, 'getApiData'])->name('dashboard.api');
    Route::get('/overview', [SupervisorDashboard::class, 'overview'])->name('overview');

    // Downtime Control
    Route::prefix('downtime')->name('downtime.')->group(function() {
        Route::get('/dashboard', [SupervisorDashboard::class, 'index'])->name('dashboard');
        Route::get('/monitoring', function() { return view('supervisor.downtime.monitoring'); })->name('monitoring');
        Route::get('/tren', function() { return view('supervisor.downtime.tren'); })->name('tren');
        Route::get('/history', function() { return view('supervisor.downtime.history'); })->name('history');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function() {
        Route::get('/daily-production', function() { return view('supervisor.reports.daily_production'); })->name('daily_production');
        Route::get('/performance', function() { return view('supervisor.reports.performance'); })->name('performance');
    });
});

// SUPERVISOR & PPC RESTRICTED FEATURES
Route::middleware(['auth','role:supervisor,ppc'])
->prefix('supervisor')
->name('supervisor.')
->group(function(){

    // Data Job
    Route::get('/job', [\App\Http\Controllers\Supervisor\JobController::class, 'index'])->name('job.index');
    Route::get('/job/create', [\App\Http\Controllers\Supervisor\JobController::class, 'create'])->name('job.create');
    Route::get('/job/update/{id?}', [\App\Http\Controllers\Supervisor\JobController::class, 'edit'])->name('job.update');
    
    // Idle Time
    Route::get('/idletime', [\App\Http\Controllers\Supervisor\IdleTimeController::class, 'index'])->name('idletime.index');
    
    // Breaktime
    Route::get('/breaktime', [\App\Http\Controllers\Supervisor\BreaktimeController::class, 'index'])->name('breaktime.index');
    Route::get('/breaktime/create', [\App\Http\Controllers\Supervisor\BreaktimeController::class, 'create'])->name('breaktime.create');
    Route::get('/breaktime/rekap', function() { return view('supervisor.breaktime.rekap'); })->name('breaktime.rekap');
    
    // Handwork
    Route::get('/handwork', [\App\Http\Controllers\Supervisor\HandworkController::class, 'index'])->name('handwork.index');
    Route::get('/handwork/select', [\App\Http\Controllers\Supervisor\HandworkController::class, 'select'])->name('handwork.select');
    Route::get('/handwork/rekap/{id?}', [\App\Http\Controllers\Supervisor\HandworkController::class, 'rekap'])->name('handwork.rekap');
    
    // Quality Check (Q-Check)
    Route::get('/qcheck', [\App\Http\Controllers\Supervisor\QCheckController::class, 'index'])->name('qcheck.index');
    Route::get('/qcheck/select', [\App\Http\Controllers\Supervisor\QCheckController::class, 'select'])->name('qcheck.select');
    Route::get('/qcheck/list/{id?}', [\App\Http\Controllers\Supervisor\QCheckController::class, 'list'])->name('qcheck.list');
    Route::get('/qcheck/form/{id?}', [\App\Http\Controllers\Supervisor\QCheckController::class, 'form'])->name('qcheck.form');
    
    // Grafik
    Route::prefix('grafik')->name('grafik.')->group(function() {
        Route::get('/downtime-item', function() { return view('supervisor.grafik.downtime_item'); })->name('downtime_item');
        Route::get('/downtime-machine', function() { return view('supervisor.grafik.downtime_machine'); })->name('downtime_machine');
        Route::get('/downtime-type', function() { return view('supervisor.grafik.downtime_type'); })->name('downtime_type');
        Route::get('/output-line', function() { return view('supervisor.grafik.output_line'); })->name('output_line');
    });
    
    // Planning
    Route::prefix('planning')->name('planning.')->group(function() {
        // Production Line CRUD
        Route::get('/production-line',                    [ProductionLineController::class, 'index']       )->name('production_line');
        Route::post('/production-line',                   [ProductionLineController::class, 'store']       )->name('production_line.store');
        Route::get('/production-line/active',             [ProductionLineController::class, 'activeLines'] )->name('production_line.active');
        Route::get('/production-line/{id}',               [ProductionLineController::class, 'show']        )->name('production_line.show');
        Route::put('/production-line/{id}',               [ProductionLineController::class, 'update']      )->name('production_line.update');
        Route::patch('/production-line/{id}/status',      [ProductionLineController::class, 'toggleStatus'])->name('production_line.status');
        Route::delete('/production-line/{id}',            [ProductionLineController::class, 'destroy']     )->name('production_line.destroy');
    });

    // Approval
    Route::prefix('approval')->name('approval.')->group(function() {
        Route::get('/production', function() { return view('supervisor.approval.production'); })->name('production');
        Route::get('/quality', function() { return view('supervisor.approval.quality'); })->name('quality');
    });

    // Quality Control
    Route::prefix('quality')->name('quality.')->group(function() {
        Route::get('/dashboard', [SupervisorDashboard::class, 'index'])->name('dashboard');
        Route::get('/defect-monitoring', function() { return view('supervisor.quality.defect_monitoring'); })->name('defect_monitoring');
        Route::get('/reject-analysis', function() { return view('supervisor.quality.reject_analysis'); })->name('reject_analysis');
    });
});


// landing 
Route::get('/', function(){
    return view('landing');
})->name('landing');

// login
Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'loginProcess'])->name('login.process');

// MONITORING
Route::prefix('monitoring')
->name('monitoring.')
->controller(MonitoringController::class)
->middleware(['auth'])
->group(function(){

    // Supervisor & PPC
    Route::middleware('role:supervisor,ppc')->group(function(){
        Route::get('/line', 'line')->name('line');
        Route::get('/machine_status','machine_status')->name('machine_status');
    });

   
});

 Route::middleware(['auth','role:operator'])
        ->prefix('operator')
        ->group(function(){

        Route::get('/dashboard', [OperatorDashboardController::class, 'index'])
            ->name('operator.dashboard');

    });

 Route::middleware(['auth','role:foreman'])
        ->prefix('foreman')
        ->group(function(){

        Route::get('/dashboard', [OperatorDashboardController::class, 'index'])
            ->name('foreman.dashboard');

    });

// logout
Route::get('/logout', function () {
    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/login');
})->name('logout');

/*
|--------------------------------------------------------------------------
| Production Entry CRUD
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/production_entry', [ProductionController::class, 'index'])
        ->name('production_entry');

    Route::post('/production_entry/store', [ProductionController::class, 'store'])
        ->name('production_entry.store');

    Route::put('/production_entry/update/{id}', [ProductionController::class, 'update'])
        ->name('production_entry.update');

    Route::delete('/production_entry/delete/{id}', [ProductionController::class, 'delete'])
        ->name('production_entry.delete');

    Route::get('/production/history', [ProductionController::class, 'history'])
        ->name('production.history');

});

// routing approval

Route::middleware(['auth','role:supervisor'])->group(function () {

    Route::prefix('approval')->group(function(){

        Route::get('/production', function(){   
            return view('approval.production_approval');
        })->name('production_approval');

        Route::get('/quality', function(){   
            return view('approval.quality_approval');
        })->name('quality_approval');

    });

});

// quality control
Route::middleware(['auth','role:supervisor'])->group(function () {

    Route::prefix('quality_control')->name('quality_control.')->group(function(){

        Route::get('/defect', function(){
            return view('quality_control.defect_monitoring');
        })->name('defect');

        Route::get('/reject', function(){
            return view('quality_control.reject_analysis');
        })->name('reject');

    });

});

// downtime 
Route::middleware(['auth','role:supervisor'])->group(function () {

    Route::prefix('downtime')->name('downtime.')->group(function(){

        Route::get('/monitoring', function(){
            return view('downtime.downtime_monitoring');
        })->name('monitoring');

        Route::get('/tren_downtime', function(){
            return view('downtime.tren_downtime');
        })->name('tren_downtime');

        Route::get('/history', [\App\Http\Controllers\Supervisor\DashboardController::class, 'troubleHistory'])->name('history');

    });

});


// reports
Route::middleware(['auth','role:supervisor'])->group(function () {

    Route::prefix('reports')->name('reports.')->group(function(){

        Route::get('/daily', function(){
            return view('reports.daily_production');
        })->name('daily_production');

        Route::get('/performance', function(){
            return view('reports.perfomance');
        })->name('performance');

    });

});

Route::middleware(['auth'])
->prefix('operational')
->name('operational.')
->group(function () {

    /*
    ====================================================
    INPUT HARIAN
    ====================================================
    */
    Route::get('/input-harian',
        [InputHarianController::class, 'index']
    )->name('input_harian');

    Route::post('/job/{id}/save',
        [InputHarianController::class, 'saveQty']
    )->name('job.save');

    Route::post('/job/{id}/save-log',
        [InputHarianController::class, 'saveProductionLog']
    )->name('job.save_log');

    Route::get('/job/{id}/logs',
        [InputHarianController::class, 'showLogs']
    )->name('job.logs.detail');

    Route::get('/audit-trail',
        [InputHarianController::class, 'productionAudit']
    )->name('audit_trail');

    /*
    ====================================================
    NEXT PROCESS
    ====================================================
    */
    Route::get('/job/{id}/next-list',
        [InputHarianController::class, 'nextList']
    )->name('job.next_list');

    Route::post('/job/{id}/next-process',
        [InputHarianController::class, 'nextProcess']
    )->name('job.next_process');

    /*
    ====================================================
    TIMER REALTIME
    ====================================================
    */
    Route::post('/job/{id}/start',
        [InputHarianController::class, 'start']
    )->name('job.start');

    Route::post('/job/{id}/dandori/start',
        [InputHarianController::class, 'startDandori']
    )->name('job.dandori.start');

    Route::post('/job/{id}/dandori/finish',
        [InputHarianController::class, 'finishDandori']
    )->name('job.dandori.finish');

    Route::post('/job/{id}/pause',
        [InputHarianController::class, 'pause']
    )->name('job.pause');

    Route::post('/job/{id}/resume',
        [InputHarianController::class, 'resume']
    )->name('job.resume');

    Route::post('/job/{id}/restart',
        [InputHarianController::class, 'restart']
    )->name('job.restart');

    Route::post('/job/{id}/start',
        [InputHarianController::class, 'start']
    )->name('job.start');

    Route::post('/job/{id}/finish',
        [InputHarianController::class, 'finish']
    )->name('job.finish');

    Route::get('/job/{id}/status',
        [InputHarianController::class, 'status']
    )->name('job.status');

    // Global active job
    Route::get('/active-job', [InputHarianController::class, 'activeJob'])->name('active-job');

    /*
    ====================================================
    DOWNTIME
    ====================================================
    */
    Route::get('/job/{job_id}/downtimes', [InputHarianController::class, 'getDowntimes'])->name('job.downtimes');
    Route::post('/job/{job_id}/downtime/start', [InputHarianController::class, 'startDowntime'])->name('job.downtime.start');
    Route::post('/downtime/{id}/finish', [InputHarianController::class, 'finishDowntime'])->name('downtime.finish');
    Route::put('/downtime/{id}/update', [InputHarianController::class, 'updateDowntime'])->name('downtime.update');
    Route::delete('/downtime/{id}/delete', [InputHarianController::class, 'deleteDowntime'])->name('downtime.delete');

    /*
    ====================================================
    DANDORI
    ====================================================
    */

    // halaman utama dandori
    Route::get('/dandori',
        [DandoriController::class, 'index']
    )->name('dandori');

    // klik tombol dari input harian
    Route::get('/dandori/create/{jobId}',
        [DandoriController::class, 'create']
    )->name('dandori.create');

    // halaman detail dandori parent-child
    Route::get('/dandori/show/{id}',
        [DandoriController::class, 'open']
    )->name('dandori.show');

    // load card atas
    Route::get('/dandori/load-jobs',
        [DandoriController::class, 'loadJobs']
    )->name('dandori.loadJobs');

    // get detail for modal
    Route::get('/dandori/get-detail/{id}',
        [DandoriController::class, 'getDetail']
    )->name('dandori.getDetail');

    // start activity
    Route::post('/dandori/start/{id}/{type}',
        [DandoriController::class, 'start']
    )->name('dandori.start');

    // stop activity
    Route::post('/dandori/stop/{id}',
        [DandoriController::class, 'stop']
    )->name('dandori.stop');

    // restart activity
    Route::post('/dandori/restart/{id}',
        [DandoriController::class, 'restart']
    )->name('dandori.restart');

    // history
    Route::get('/dandori/history',
        [DandoriController::class, 'history']
    )->name('dandori.history');

    /*
    ====================================================
    IDLE TIME
    ====================================================
    */
    Route::get('/idle', function () {
        return view('operational.idle');
    })->name('idle');

    /*
    ====================================================
    BREAK TIME
    ====================================================
    */
    Route::get('/break', function () {
        return view('operational.break');
    })->name('break');

    /*
    ====================================================
    HANDWORK
    ====================================================
    */
    Route::get('/handwork', function () {
        return view('operational.handwork');
    })->name('handwork');

    /*
    ====================================================
    Q-CHECK
    ====================================================
    */
    Route::get('/qcheck', function () {
        return view('operational.qcheck');
    })->name('qcheck');

});

/*
|--------------------------------------------------------------------------
| Other Pages
|--------------------------------------------------------------------------
*/

// Production
Route::get('/production_recap', [ProductionController::class, 'recap'])
    ->name('production_recap');

Route::get('/production_recap/export', [ProductionController::class, 'export'])
    ->name('production_recap.export');



// quality

Route::prefix('quality')->group(function () {

    Route::get('/', [QualityController::class, 'index'])->name('quality.index');

    Route::post('/store', [QualityController::class, 'store'])->name('quality.store');

    Route::get('/edit/{id}', [QualityController::class, 'edit'])->name('quality.edit');

    Route::post('/update/{id}', [QualityController::class, 'update'])->name('quality.update');

    Route::delete('/delete/{id}', [QualityController::class, 'destroy'])->name('quality.delete');

});

// grafik
Route::middleware(['auth'])->group(function () {

    Route::prefix('grafik')->group(function () {

        // Tren Output
        Route::get('/tren-output', function () {
            return view('grafik.tren_output');
        })->name('grafik.output');

        // Pencapaian Kualitas
        Route::get('/pencapaian-kualitas', function () {
            return view('grafik.pencapaiaan_kualitas');
        })->name('grafik.quality');

    });

});

// ======================
// PPC
// ======================
Route::middleware(['auth', 'role:ppc'])->prefix('ppc')->name('ppc.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Ppc\DashboardController::class, 'index'])->name('dashboard');

    // Production Planning
    Route::prefix('planning')->name('planning.')->group(function() {
        Route::get('/production-plan',                 [ProductionPlanController::class, 'index']  )->name('production_plan');
        Route::post('/production-plan',                [ProductionPlanController::class, 'store']  )->name('production_plan.store');
        Route::post('/production-plan/import',         [ProductionPlanController::class, 'import'] )->name('production_plan.import');
        Route::post('/production-plan/update-inline',  [ProductionPlanController::class, 'updateInline'])->name('production_plan.inline');
        Route::get('/production-plan/{id}',            [ProductionPlanController::class, 'show']   )->name('production_plan.show');
        Route::put('/production-plan/{id}',            [ProductionPlanController::class, 'update'] )->name('production_plan.update');
        Route::delete('/production-plan/{id}',         [ProductionPlanController::class, 'destroy'])->name('production_plan.destroy');
    });
});