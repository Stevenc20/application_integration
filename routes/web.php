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
use App\Http\Controllers\Admin\KaryawanController;
Use App\Http\Controllers\Operational\InputHarianController;
use App\Http\Controllers\Operational\DandoriController;
use App\Http\Controllers\Ppc\ProductionPlanController;
use App\Http\Controllers\Planning\ProductionLineController;
use App\Http\Controllers\Ppc\RundownController;
use App\Http\Controllers\Ppc\RundownPressController;
use App\Http\Controllers\Ppc\BomController;
use App\Http\Controllers\Ppc\ProductionOrderController;
use App\Http\Controllers\Ppc\MrpController;
use App\Http\Controllers\Ppc\MasterStampingController;
use App\Http\Controllers\Ppc\StockController;

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
Route::middleware(['auth','role:admin,supervisor,ppc'])->prefix('admin')->name('admin.')->group(function(){

    Route::get('/dashboard', [AdminDashboardController::class,'index'])
        ->middleware('feature:dashboard')
        ->name('dashboard');

    Route::resource('/users', UserController::class)
        ->middleware('feature:user_management');

});
// SUPER ADMIN
Route::middleware(['auth', 'role:superadmin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\SuperAdmin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', [\App\Http\Controllers\SuperAdmin\UserController::class, 'index'])->name('users.index');
    Route::post('/users', [\App\Http\Controllers\SuperAdmin\UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [\App\Http\Controllers\SuperAdmin\UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [\App\Http\Controllers\SuperAdmin\UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/features', [\App\Http\Controllers\SuperAdmin\FeatureController::class, 'index'])->name('features.index');
    Route::post('/features', [\App\Http\Controllers\SuperAdmin\FeatureController::class, 'update'])->name('features.update');
    Route::get('/assignments', [\App\Http\Controllers\SuperAdmin\LineAssignmentController::class, 'index'])->name('assignments.index');
    Route::post('/assignments', [\App\Http\Controllers\SuperAdmin\LineAssignmentController::class, 'store'])->name('assignments.store');
    Route::delete('/assignments/{assignment}', [\App\Http\Controllers\SuperAdmin\LineAssignmentController::class, 'destroy'])->name('assignments.destroy');
    Route::get('/recycle-bin', [\App\Http\Controllers\SuperAdmin\RecycleBinController::class, 'index'])->name('recycle-bin.index');
    Route::get('/recycle-bin/stats', [\App\Http\Controllers\SuperAdmin\RecycleBinController::class, 'stats'])->name('recycle-bin.stats');
    Route::post('/recycle-bin/{id}/restore', [\App\Http\Controllers\SuperAdmin\RecycleBinController::class, 'restore'])->name('recycle-bin.restore');
    Route::delete('/recycle-bin/{id}', [\App\Http\Controllers\SuperAdmin\RecycleBinController::class, 'forceDelete'])->name('recycle-bin.destroy');
    Route::post('/recycle-bin/restore-all', [\App\Http\Controllers\SuperAdmin\RecycleBinController::class, 'restoreAll'])->name('recycle-bin.restore-all');
});

// Signature TTD
Route::middleware(['auth'])->prefix('signature')->name('signature.')->group(function () {
    Route::get('/status', [\App\Http\Controllers\SignatureController::class, 'status'])->name('status');
    Route::get('/get', [\App\Http\Controllers\SignatureController::class, 'get'])->name('get');
    Route::post('/save', [\App\Http\Controllers\SignatureController::class, 'save'])->name('save');
    Route::post('/delete', [\App\Http\Controllers\SignatureController::class, 'delete'])->name('delete');
});


// master
Route::middleware(['auth','role:admin,ppc,supervisor', 'feature:job_master'])->prefix('master/job')->group(function () {

    Route::get('/', [JobController::class, 'index'])->name('master.job');

    Route::post('/store', [JobController::class, 'store'])->name('master.job.store');

    Route::put('/update/{id}', [JobController::class, 'update']);

    Route::get('/delete/{id}', [JobController::class, 'delete']);

    Route::get('/edit/{id}', [JobController::class, 'edit']);

});

// master karyawan
Route::middleware(['auth','role:admin,ppc,supervisor', 'feature:data_karyawan'])->prefix('master/karyawan')->group(function () {

    Route::get('/', [KaryawanController::class, 'index'])->name('master.karyawan');

    Route::post('/store', [KaryawanController::class, 'store'])->name('master.karyawan.store');

    Route::put('/update/{id}', [KaryawanController::class, 'update'])->name('master.karyawan.update');

    Route::get('/delete/{id}', [KaryawanController::class, 'delete'])->name('master.karyawan.delete');

});

// SHARED SUPERVISOR ROUTES FOR DASHBOARDS, DOWNTIME, AND REPORTS
Route::middleware(['auth','role:supervisor,ppc,leader,foreman,manager,kadiv,direktur,presdir'])
->prefix('supervisor')
->name('supervisor.')
->group(function(){

    Route::get('/dashboard', [SupervisorDashboard::class, 'index'])->middleware('feature:dashboard')->name('dashboard');
    Route::get('/dashboard/api-data', [SupervisorDashboard::class, 'getApiData'])->middleware('feature:dashboard')->name('dashboard.api');
    Route::get('/dashboard/detail', [SupervisorDashboard::class, 'getDetailData'])->middleware('feature:dashboard')->name('dashboard.detail');
    Route::get('/dashboard/stream', [SupervisorDashboard::class, 'stream'])->middleware('feature:dashboard')->name('dashboard.stream');
    Route::get('/monitor', [SupervisorDashboard::class, 'monitor'])->middleware('feature:dashboard')->name('monitor');
    Route::get('/overview', [SupervisorDashboard::class, 'overview'])->middleware('feature:dashboard')->name('overview');
    Route::get('/overview/data', [SupervisorDashboard::class, 'overviewData'])->middleware('feature:dashboard')->name('overview.data');
    Route::get('/overview/all-logs', [SupervisorDashboard::class, 'allProductionLogs'])->middleware('feature:dashboard')->name('overview.allLogs');
    Route::get('/overview/line-status', [SupervisorDashboard::class, 'overviewLineStatus'])->middleware('feature:dashboard')->name('overview.lineStatus');
    Route::get('/overview/line-status/{line}', [SupervisorDashboard::class, 'lineStatusSingle'])->middleware('feature:dashboard')->name('overview.lineStatusSingle');

    // Reports
    Route::prefix('reports')->name('reports.')->group(function() {
        Route::get('/daily-production', [\App\Http\Controllers\Supervisor\ReportController::class, 'dailyProduction'])->middleware('feature:daily_report')->name('daily_production');
        Route::get('/performance', [\App\Http\Controllers\Supervisor\ReportController::class, 'performance'])->middleware('feature:performance_report')->name('performance');
        Route::get('/downtime-recap/{planId}', [\App\Http\Controllers\Supervisor\ReportController::class, 'downtimeRecap'])->middleware('feature:daily_report')->name('downtime_recap');
        Route::get('/downtime-recap-json/{planId}', [\App\Http\Controllers\Supervisor\ReportController::class, 'downtimeRecapJson'])->middleware('feature:daily_report')->name('downtime_recap_json');
        Route::get('/handwork-recap/{planId}', [\App\Http\Controllers\Supervisor\ReportController::class, 'handworkRecap'])->middleware('feature:daily_report')->name('handwork_recap');
    });

    // Grafik API
    Route::prefix('api/grafik')->name('api.grafik.')->group(function() {
        Route::get('/quality',             [\App\Http\Controllers\Api\GrafikController::class, 'quality']        )->name('quality');
        Route::get('/downtime-item',       [\App\Http\Controllers\Api\GrafikController::class, 'downtimeItem']  )->name('downtime_item');
        Route::get('/downtime-type',       [\App\Http\Controllers\Api\GrafikController::class, 'downtimeType']  )->name('downtime_type');
        Route::get('/downtime-machine',    [\App\Http\Controllers\Api\GrafikController::class, 'downtimeMachine'])->name('downtime_machine');
    });
});

// SUPERVISOR, PPC, FOREMAN & LEADER RESTRICTED FEATURES
Route::middleware(['auth','role:supervisor,ppc,foreman,leader,manager,kadiv,direktur,quality'])
->prefix('supervisor')
->name('supervisor.')
->group(function(){

    // Data Job
    Route::get('/job', [\App\Http\Controllers\Supervisor\JobController::class, 'index'])->middleware('feature:data_job')->name('job.index');
    Route::get('/job/create', [\App\Http\Controllers\Supervisor\JobController::class, 'create'])->middleware('feature:data_job')->name('job.create');
    Route::get('/job/update/{id?}', [\App\Http\Controllers\Supervisor\JobController::class, 'edit'])->middleware('feature:data_job')->name('job.update');
    
    // Breaktime
    Route::get('/breaktime', [\App\Http\Controllers\Supervisor\BreaktimeController::class, 'index'])->middleware('feature:breaktime')->name('breaktime.index');
    Route::get('/breaktime/create', [\App\Http\Controllers\Supervisor\BreaktimeController::class, 'create'])->middleware('feature:breaktime')->name('breaktime.create');
    Route::post('/breaktime', [\App\Http\Controllers\Supervisor\BreaktimeController::class, 'store'])->middleware('feature:breaktime')->name('breaktime.store');
    Route::get('/breaktime/{id}/edit', [\App\Http\Controllers\Supervisor\BreaktimeController::class, 'edit'])->middleware('feature:breaktime')->name('breaktime.update');
    Route::put('/breaktime/{id}', [\App\Http\Controllers\Supervisor\BreaktimeController::class, 'update'])->middleware('feature:breaktime');
    Route::delete('/breaktime/{id}', [\App\Http\Controllers\Supervisor\BreaktimeController::class, 'destroy'])->middleware('feature:breaktime')->name('breaktime.delete');
    Route::get('/breaktime/rekap', function() { return view('supervisor.breaktime.rekap'); })->middleware('feature:breaktime')->name('breaktime.rekap');

    Route::prefix('api/breaktime-parameters')->name('api.breaktime.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\BreakTimeParameterController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Api\BreakTimeParameterController::class, 'store'])->name('store');
        Route::put('/{id}', [\App\Http\Controllers\Api\BreakTimeParameterController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Api\BreakTimeParameterController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/toggle', [\App\Http\Controllers\Api\BreakTimeParameterController::class, 'toggle'])->name('toggle');
        Route::get('/preview', [\App\Http\Controllers\Api\BreakTimeParameterController::class, 'preview'])->name('preview');
        Route::post('/simulate', [\App\Http\Controllers\Api\BreakTimeParameterController::class, 'simulate'])->name('simulate');
    });
    
    // Handwork
    Route::get('/handwork', [\App\Http\Controllers\Supervisor\HandworkController::class, 'index'])->middleware(['auth', 'feature:handwork'])->name('handwork.index');
    Route::get('/handwork/api/detail/{planId}', [\App\Http\Controllers\Supervisor\HandworkController::class, 'getJobDetail'])->middleware(['auth', 'feature:handwork'])->name('handwork.api.detail');
    Route::post('/handwork/api/store', [\App\Http\Controllers\Supervisor\HandworkController::class, 'storeItem'])->middleware(['auth', 'feature:handwork'])->name('handwork.api.store');
    Route::delete('/handwork/api/delete/{id}', [\App\Http\Controllers\Supervisor\HandworkController::class, 'deleteItem'])->middleware(['auth', 'feature:handwork'])->name('handwork.api.delete');
    
    // Quality Check (Q-Check)
    Route::get('/qcheck', [\App\Http\Controllers\Supervisor\QCheckController::class, 'index'])->middleware('feature:qcheck')->name('qcheck.index');
    Route::get('/qcheck/select', [\App\Http\Controllers\Supervisor\QCheckController::class, 'select'])->middleware('feature:qcheck')->name('qcheck.select');
    Route::get('/qcheck/list/{id?}', [\App\Http\Controllers\Supervisor\QCheckController::class, 'list'])->middleware('feature:qcheck')->name('qcheck.list');
    Route::get('/qcheck/form/{id?}', [\App\Http\Controllers\Supervisor\QCheckController::class, 'form'])->middleware('feature:qcheck')->name('qcheck.form');
    Route::post('/qcheck/store', [\App\Http\Controllers\Supervisor\QCheckController::class, 'store'])->middleware('feature:qcheck')->name('qcheck.store');
    Route::put('/qcheck/update/{id}', [\App\Http\Controllers\Supervisor\QCheckController::class, 'update'])->middleware('feature:qcheck')->name('qcheck.update');
    Route::delete('/qcheck/delete/{id}', [\App\Http\Controllers\Supervisor\QCheckController::class, 'destroy'])->middleware('feature:qcheck')->name('qcheck.destroy');
    
    // Grafik
    Route::prefix('grafik')->name('grafik.')->middleware('feature:grafik_downtime_item')->group(function() {
        Route::get('/downtime-item', function() { return view('supervisor.grafik.downtime_item'); })->name('downtime_item');
        Route::get('/downtime-machine', function() { return view('supervisor.grafik.downtime_machine'); })->name('downtime_machine');
        Route::get('/downtime-type', function() { return view('supervisor.grafik.downtime_type'); })->name('downtime_type');
    });
    
    // Planning
    Route::prefix('planning')->name('planning.')->middleware('feature:production_line')->group(function() {
        // Production Line CRUD
        Route::get('/production-line',                    [ProductionLineController::class, 'index']       )->name('production_line');
        Route::post('/production-line',                   [ProductionLineController::class, 'store']       )->name('production_line.store');
        Route::get('/production-line/active',             [ProductionLineController::class, 'activeLines'] )->name('production_line.active');
        Route::get('/production-line/{id}',               [ProductionLineController::class, 'show']        )->name('production_line.show');
        Route::put('/production-line/{id}',               [ProductionLineController::class, 'update']      )->name('production_line.update');
        Route::patch('/production-line/{id}/status',      [ProductionLineController::class, 'toggleStatus'])->name('production_line.status');
        Route::delete('/production-line/{id}',            [ProductionLineController::class, 'destroy']     )->name('production_line.destroy');
    });

    // Quality Control
    Route::prefix('quality')->name('quality.')->group(function() {
        Route::get('/dashboard', [SupervisorDashboard::class, 'index'])->middleware('feature:quality_dashboard')->name('dashboard');
        Route::get('/defect-monitoring', function() { return view('supervisor.quality.defect_monitoring'); })->middleware('feature:quality_control_defect')->name('defect_monitoring');
        Route::get('/reject-analysis', function() { return view('supervisor.quality.reject_analysis'); })->middleware('feature:quality_control_reject')->name('reject_analysis');
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

    // Supervisor, PPC & Management
        Route::middleware('role:supervisor,foreman,ppc,manager,kadiv,direktur,presdir,quality')->group(function(){
            Route::get('/line', 'line')->middleware('feature:line_monitoring')->name('line');
            Route::get('/line/api-data', 'lineApiData')->middleware('feature:line_monitoring')->name('line.api');
            Route::get('/machine-status', 'machine_status')->middleware('feature:line_monitoring')->name('machine_status');
            Route::get('/history/{type}', 'history')->middleware('feature:line_monitoring')->name('history');
            Route::get('/downtime-list', 'downtimeList')->name('downtime.list');
            Route::get('/tryout', 'tryout')->name('tryout');
            Route::get('/unedited-count', 'uneditedCount')->middleware('feature:line_monitoring')->name('unedited-count');
        });

   
});

 Route::middleware(['auth','role:operator'])
        ->prefix('operator')
        ->group(function(){

        Route::get('/dashboard', [OperatorDashboardController::class, 'index'])
            ->middleware('feature:dashboard')
            ->name('operator.dashboard');

    });

 Route::middleware(['auth','role:foreman'])
        ->prefix('foreman')
        ->group(function(){

        Route::get('/dashboard', [OperatorDashboardController::class, 'index'])
            ->middleware('feature:dashboard')
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
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [App\Http\Controllers\ProfileController::class, 'updateAvatar'])->name('profile.avatar');
});

// quality control
Route::middleware(['auth','role:supervisor'])->group(function () {

    Route::prefix('quality_control')->name('quality_control.')->group(function(){

        Route::get('/defect', function(){
            return view('quality_control.defect_monitoring');
        })->middleware('feature:quality_control_defect')->name('defect');

        Route::get('/reject', function(){
            return view('quality_control.reject_analysis');
        })->middleware('feature:quality_control_reject')->name('reject');

    });

});

// downtime history (functional)
Route::middleware(['auth','role:supervisor,foreman,leader,manager,kadiv'])->group(function () {
    Route::prefix('downtime')->name('downtime.')->group(function(){
        Route::get('/history', [\App\Http\Controllers\Supervisor\DashboardController::class, 'troubleHistory'])->middleware('feature:trouble_history')->name('history');
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
    )->middleware('feature:input_harian')->name('input_harian');

    Route::post('/job/{id}/save',
        [InputHarianController::class, 'saveQty']
    )->name('job.save');

    Route::post('/job/{id}/save-log',
        [InputHarianController::class, 'saveProductionLog']
    )->name('job.save_log');

    Route::get('/job/{id}/logs',
        [InputHarianController::class, 'showLogs']
    )->name('job.logs.detail');

    Route::get('/job/{id}/qty',
        [InputHarianController::class, 'getQty']
    )->name('job.qty');

    Route::get('/job/{id}/sync',
        [InputHarianController::class, 'sync']
    )->name('job.sync');

    Route::get('/audit-trail',
        [InputHarianController::class, 'productionAudit']
    )->middleware('feature:audit_trail')->name('audit_trail');

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

    Route::post('/job/{id}/dandori/first-check/start',
        [InputHarianController::class, 'startFirstCheck']
    )->name('job.dandori.firstCheck.start');

    Route::post('/job/{id}/dandori/first-check/finish',
        [InputHarianController::class, 'finishFirstCheck']
    )->name('job.dandori.firstCheck.finish');

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

    Route::post('/job/{id}/enqueue',
        [InputHarianController::class, 'enqueue']
    )->name('job.enqueue');

    Route::post('/job/{id}/finish',
        [InputHarianController::class, 'finish']
    )->name('job.finish');

    Route::get('/job/{id}/status',
        [InputHarianController::class, 'status']
    )->name('job.status');

    // Global active job
    Route::get('/active-job', [InputHarianController::class, 'activeJob'])->name('active-job');

    // End-of-shift submission
    Route::post('/shift/{lineId}/submit', [InputHarianController::class, 'submitShift'])->name('shift.submit');

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
    )->middleware('feature:dandori')->name('dandori');

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
        return redirect()->route('supervisor.handwork.index');
    })->name('handwork');

    /*
    ====================================================
    Q-CHECK
    ====================================================
    */
    Route::get('/qcheck', function () {
        return view('operational.qcheck');
    })->name('qcheck');

    /*
    ====================================================
    REPAIR & REJECT
    ====================================================
    */
    Route::get('/repair-reject',           [\App\Http\Controllers\Operational\RepairRejectController::class, 'index'])->middleware('feature:repair_reject')->name('repair_reject.index');
    Route::post('/repair-reject',          [\App\Http\Controllers\Operational\RepairRejectController::class, 'store'])->middleware('feature:repair_reject')->name('repair_reject.store');
    Route::post('/repair-reject/{id}/update', [\App\Http\Controllers\Operational\RepairRejectController::class, 'update'])->middleware('feature:repair_reject')->name('repair_reject.update');
    Route::delete('/repair-reject/{id}',   [\App\Http\Controllers\Operational\RepairRejectController::class, 'destroy'])->middleware('feature:repair_reject')->name('repair_reject.destroy');
    Route::get('/job/{jobId}/repair-reject', [\App\Http\Controllers\Operational\RepairRejectController::class, 'getByJob'])->middleware('feature:repair_reject')->name('repair_reject.by_job');

});


/*
|--------------------------------------------------------------------------
| Other Pages
|--------------------------------------------------------------------------
*/

// Production
Route::middleware(['auth'])->group(function () {
    Route::get('/production_recap', [ProductionController::class, 'recap'])
        ->middleware('feature:production_recap')
        ->name('production_recap');

    Route::get('/production_recap/export', [ProductionController::class, 'export'])
        ->middleware('feature:production_recap')
        ->name('production_recap.export');
});



// quality dashboard
Route::middleware(['auth', 'role:quality'])->prefix('quality')->name('quality.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Supervisor\DashboardController::class, 'index'])->middleware('feature:quality_dashboard')->name('dashboard');
});

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

        // Pencapaian Kualitas
        Route::get('/pencapaian-kualitas', function () {
            return view('grafik.pencapaiaan_kualitas');
        })->middleware('feature:quality_achievement')->name('grafik.quality');

    });

});

// ======================
// PPC
// ======================
Route::middleware(['auth', 'role:ppc'])->prefix('ppc')->name('ppc.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Ppc\DashboardController::class, 'index'])->middleware('feature:dashboard')->name('dashboard');

    // Production Planning
    Route::prefix('planning')->name('planning.')->middleware('feature:production_plan')->group(function() {
        Route::get('/production-plan',                 [ProductionPlanController::class, 'index']  )->name('production_plan');
        Route::post('/production-plan',                [ProductionPlanController::class, 'store']  )->name('production_plan.store');
        Route::post('/production-plan/import',         [ProductionPlanController::class, 'import'] )->name('production_plan.import');
        Route::post('/production-plan/add-job',       [ProductionPlanController::class, 'addJob'])->name('production_plan.add_job');
        Route::post('/production-plan/update-inline',  [ProductionPlanController::class, 'updateInline'])->name('production_plan.inline');
        Route::post('/production-plan/reorder',        [ProductionPlanController::class, 'reorder'])->name('production_plan.reorder');
        Route::post('/production-plan/move',           [ProductionPlanController::class, 'movePlanItem'])->name('production_plan.move');
        Route::post('/production-plan/force-overflow', [ProductionPlanController::class, 'forceOverflow'])->name('production_plan.force_overflow');
        Route::post('/production-plan/{id}/recalculate',[ProductionPlanController::class, 'recalculate'])->name('production_plan.recalculate');
        Route::get('/production-plan/{id}',            [ProductionPlanController::class, 'show']   )->name('production_plan.show');
        Route::put('/production-plan/{id}',            [ProductionPlanController::class, 'update'] )->name('production_plan.update');
        Route::delete('/production-plan/{id}',         [ProductionPlanController::class, 'destroy'])->name('production_plan.destroy');

        // Clear Data
        Route::get('/clear-data',                      [ProductionPlanController::class, 'clearDataForm'])->name('production_plan.clear_form');
        Route::delete('/clear-data',                   [ProductionPlanController::class, 'clearData'])->name('production_plan.clear');

        // Recovery Schedule (Legacy routes still point to ProductionPlanController for backward compat)
        Route::post('/recovery/{id}/approve',          [ProductionPlanController::class, 'approveRecovery'])->name('recovery.approve');
        Route::post('/recovery/{id}/reject',           [ProductionPlanController::class, 'rejectRecovery'])->name('recovery.reject');
        Route::post('/recovery/approve-items',         [ProductionPlanController::class, 'approveItems'])->name('recovery.approve_items');
        Route::post('/recovery/cancel-approval',       [ProductionPlanController::class, 'cancelApproval'])->name('recovery.cancel_approval');

        // Recovery Queue & History (combined page with tabs)
        Route::get('/recovery',                        [\App\Http\Controllers\Ppc\RecoveryController::class, 'index'])->name('recovery.index');
        Route::get('/recovery-history',                [\App\Http\Controllers\Ppc\RecoveryController::class, 'index'])->name('recovery.history'); // backward compat
        Route::post('/recovery/reject-item/{id}',      [\App\Http\Controllers\Ppc\RecoveryController::class, 'rejectItem'])->name('recovery.reject_item');
        Route::post('/recovery/reject-items',          [\App\Http\Controllers\Ppc\RecoveryController::class, 'rejectItems'])->name('recovery.reject_items');

        // Cut-off & Scheduler manual trigger
        Route::post('/recovery/run-cutoff',            [\App\Http\Controllers\Ppc\RecoveryController::class, 'runCutOff'])->name('recovery.run_cutoff');
        Route::post('/recovery/run-scheduler',         [\App\Http\Controllers\Ppc\RecoveryController::class, 'runScheduler'])->name('recovery.run_scheduler');
        Route::get('/recovery/alert-data',             [\App\Http\Controllers\Ppc\RecoveryController::class, 'alertData'])->name('recovery.alert_data');
    });
});

// ======================
// PPC - Sub Modules (from IPPI-PPLC)
// ======================
Route::middleware(['auth', 'role:ppc'])->prefix('ppc')->group(function () {

    // Dashboard Stock
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');

    // Rundown Stock
    Route::get('/rundown-stock', [RundownController::class, 'index'])->name('rundown.index');

    // Simulasi Press (Rundown Press)
    Route::prefix('rundown-press')->name('rundown_press.')->group(function () {
        Route::get('/', [RundownPressController::class, 'index'])->name('index');
        Route::post('/upload', [RundownPressController::class, 'upload'])->name('upload');
        Route::post('/update-inline', [RundownPressController::class, 'updateInline'])->name('inline');
        Route::get('/sync-to-stamping', [RundownPressController::class, 'syncAllToScheduleStamping'])->name('sync_stamping');
    });

    // Bill of Materials (BOM)
    Route::prefix('boms')->name('boms.')->group(function () {
        Route::get('/', [BomController::class, 'index'])->name('index');
        Route::get('/create', [BomController::class, 'create'])->name('create');
        Route::post('/', [BomController::class, 'store'])->name('store');
        Route::get('/export', [BomController::class, 'exportExcel'])->name('export');
        Route::get('/template', [BomController::class, 'downloadTemplate'])->name('template');
        Route::post('/import', [BomController::class, 'importExcel'])->name('import');
        Route::get('/print-pdf', [BomController::class, 'printPdf'])->name('print_pdf');
        Route::get('/{id}', [BomController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [BomController::class, 'edit'])->name('edit');
        Route::put('/{id}', [BomController::class, 'update'])->name('update');
        Route::delete('/{id}', [BomController::class, 'destroy'])->name('destroy');
    });

    // Production Orders
    Route::prefix('production-orders')->name('production_orders.')->group(function () {
        Route::get('/', [ProductionOrderController::class, 'index'])->name('index');
        Route::get('/create', [ProductionOrderController::class, 'create'])->name('create');
        Route::post('/', [ProductionOrderController::class, 'store'])->name('store');
        Route::get('/print-all', [ProductionOrderController::class, 'printAll'])->name('print_all');
        Route::post('/bulk-release', [ProductionOrderController::class, 'bulkRelease'])->name('bulk_release');
        Route::get('/{id}', [ProductionOrderController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [ProductionOrderController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ProductionOrderController::class, 'update'])->name('update');
        Route::delete('/{id}', [ProductionOrderController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/print', [ProductionOrderController::class, 'print'])->name('print');
        Route::post('/{id}/release', [ProductionOrderController::class, 'release'])->name('release');
        Route::post('/{id}/issue', [ProductionOrderController::class, 'goodsIssue'])->name('issue');
        Route::post('/{id}/confirm', [ProductionOrderController::class, 'confirm'])->name('confirm');
        Route::post('/{id}/cancel', [ProductionOrderController::class, 'cancel'])->name('cancel');
    });

    // MRP
    Route::prefix('mrp')->name('mrp.')->group(function () {
        Route::get('/', [MrpController::class, 'index'])->name('index');
        Route::post('/run', [MrpController::class, 'run'])->name('run');
        Route::get('/export-pdf', [MrpController::class, 'exportListPdf'])->name('export-pdf');
        Route::get('/demands/template', [MrpController::class, 'downloadDemandTemplate'])->name('demands.template');
        Route::post('/demands/import', [MrpController::class, 'importDemands'])->name('demands.import');
        Route::delete('/demands/clear', [MrpController::class, 'clearDemands'])->name('demands.clear');
        Route::delete('/demands/{mrpDemand}', [MrpController::class, 'destroyDemand'])->name('demands.destroy');
        Route::get('/{id}', [MrpController::class, 'show'])->name('show');
        Route::get('/{id}/excel', [MrpController::class, 'exportExcel'])->name('excel');
        Route::get('/{id}/pdf', [MrpController::class, 'exportPdf'])->name('pdf');
        Route::delete('/{id}', [MrpController::class, 'destroy'])->name('destroy');
    });

    // Master Data Stamping
    Route::prefix('master-stamping')->name('master_stamping.')->group(function () {
        Route::get('/', [MasterStampingController::class, 'index'])->name('index');
        Route::post('/import', [MasterStampingController::class, 'import'])->name('import');
        Route::get('/search', [MasterStampingController::class, 'searchAjax'])->name('search');
    });

    // Schedule Stamping
    Route::prefix('schedule-stamping')->name('schedule_stamping.')->controller(\App\Http\Controllers\Ppc\ScheduleStampingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/export', 'export')->name('export');
        Route::post('/upload', 'upload')->name('upload');
        Route::post('/update-inline', 'updateInline')->name('inline');
        Route::post('/add-job', 'addJob')->name('add_job');
        Route::delete('/delete-job/{id}', 'deleteJob')->name('delete_job');
        Route::post('/reorder', 'reorder')->name('reorder');
        Route::post('/add-breaks', 'addStandardBreaks')->name('add_breaks');
        Route::post('/recalibrate-section', 'recalibrateSection')->name('recalibrate_section');
        Route::post('/recalibrate-all', 'recalibrateAll')->name('recalibrate_all');
        Route::post('/save-sect-head', 'saveSectHeadPpc')->name('save_sect_head');
    });
});

// ======================
// IRM (Inventory & Raw Material)
// ======================
Route::middleware(['auth', 'role:irm'])->prefix('irm')->group(function () {

    // Dashboard
    Route::get('/dashboard', function () { return redirect()->route('materials.index'); })->name('irm.dashboard');

    // ========== MASTER DATA ==========
    Route::resource('vendors', \App\Http\Controllers\VendorController::class)->names('vendors')->except(['create','show','edit','update']);
    Route::post('vendors/update', [\App\Http\Controllers\VendorController::class, 'update'])->name('vendors.update');
    Route::get('vendors/export/excel', [\App\Http\Controllers\VendorController::class, 'exportExcel'])->name('vendors.export');
    Route::get('vendors/print-pdf', [\App\Http\Controllers\VendorController::class, 'printPdf'])->name('vendors.print_pdf');
    Route::get('vendors/template/download', [\App\Http\Controllers\VendorController::class, 'downloadTemplate'])->name('vendors.template');
    Route::post('vendors/import', [\App\Http\Controllers\VendorController::class, 'importExcel'])->name('vendors.import');

    Route::resource('materials', \App\Http\Controllers\MaterialController::class)->names('materials')->except(['update']);
    Route::post('materials/update', [\App\Http\Controllers\MaterialController::class, 'update'])->name('materials.update');
    Route::get('materials/export/excel', [\App\Http\Controllers\MaterialController::class, 'exportExcel'])->name('materials.export');
    Route::get('materials/print-pdf', [\App\Http\Controllers\MaterialController::class, 'printPdf'])->name('materials.print_pdf');
    Route::get('materials/template/download', [\App\Http\Controllers\MaterialController::class, 'downloadTemplate'])->name('materials.template');
    Route::post('materials/import', [\App\Http\Controllers\MaterialController::class, 'importExcel'])->name('materials.import');

    Route::resource('customers', \App\Http\Controllers\CustomerController::class)->names('customers')->except(['create','show','edit','update']);
    Route::post('customers/update', [\App\Http\Controllers\CustomerController::class, 'update'])->name('customers.update');
    Route::get('customers/export/excel', [\App\Http\Controllers\CustomerController::class, 'exportExcel'])->name('customers.export');
    Route::get('customers/print-pdf', [\App\Http\Controllers\CustomerController::class, 'printPdf'])->name('customers.print_pdf');
    Route::get('customers/template/download', [\App\Http\Controllers\CustomerController::class, 'downloadTemplate'])->name('customers.template');
    Route::post('customers/import', [\App\Http\Controllers\CustomerController::class, 'importExcel'])->name('customers.import');

    Route::resource('storage-locations', \App\Http\Controllers\StorageLocationController::class)->names('storage_locations')->except(['show','edit','update']);
    Route::post('storage-locations/update', [\App\Http\Controllers\StorageLocationController::class, 'update'])->name('storage_locations.update');
    Route::get('storage-locations/export/excel', [\App\Http\Controllers\StorageLocationController::class, 'exportExcel'])->name('storage_locations.export');
    Route::get('storage-locations/print-pdf', [\App\Http\Controllers\StorageLocationController::class, 'printPdf'])->name('storage_locations.print_pdf');
    Route::get('storage-locations/template/download', [\App\Http\Controllers\StorageLocationController::class, 'downloadTemplate'])->name('storage_locations.template');
    Route::post('storage-locations/import', [\App\Http\Controllers\StorageLocationController::class, 'importExcel'])->name('storage_locations.import');

    // ========== TRANSACTIONS ==========
    Route::resource('purchase-orders', \App\Http\Controllers\PurchaseOrderController::class)->names('purchase_orders');
    Route::get('purchase-orders/{id}/detail-pdf', [\App\Http\Controllers\PurchaseOrderController::class, 'printDetailPdf'])->name('purchase_orders.detail_pdf');
    Route::get('purchase-orders/export/list-pdf', [\App\Http\Controllers\PurchaseOrderController::class, 'printPdf'])->name('purchase_orders.print_pdf');
    Route::get('purchase-orders/export/excel', [\App\Http\Controllers\PurchaseOrderController::class, 'exportExcel'])->name('purchase_orders.export');
    Route::get('purchase-orders/import-template', [\App\Http\Controllers\PurchaseOrderController::class, 'downloadTemplate'])->name('purchase_orders.import-template');
    Route::post('purchase-orders/import', [\App\Http\Controllers\PurchaseOrderController::class, 'importExcel'])->name('purchase_orders.import');
    Route::post('purchase-orders/import-excel', [\App\Http\Controllers\PurchaseOrderController::class, 'importExcel'])->name('purchase_orders.import-excel');
    Route::get('purchase-orders/import-create', [\App\Http\Controllers\PurchaseOrderController::class, 'importCreate'])->name('purchase_orders.import-create');
    Route::post('purchase-orders/{id}/approve', [\App\Http\Controllers\PurchaseOrderController::class, 'approve'])->name('purchase_orders.approve');
    Route::post('purchase-orders/{id}/cancel', [\App\Http\Controllers\PurchaseOrderController::class, 'cancel'])->name('purchase_orders.cancel');

    Route::get('summary-kanban', [\App\Http\Controllers\SummaryKanbanController::class, 'index'])->name('summary_kanban.index');
    Route::get('summary-kanban/create', [\App\Http\Controllers\SummaryKanbanController::class, 'create'])->name('summary_kanban.create');
    Route::post('summary-kanban', [\App\Http\Controllers\SummaryKanbanController::class, 'store'])->name('summary_kanban.store');
    Route::get('summary-kanban/{id}', [\App\Http\Controllers\SummaryKanbanController::class, 'show'])->name('summary_kanban.show');
    Route::post('summary-kanban/{id}/status', [\App\Http\Controllers\SummaryKanbanController::class, 'updateStatus'])->name('summary_kanban.status');
    Route::post('summary-kanban/{id}/generate-po', [\App\Http\Controllers\SummaryKanbanController::class, 'generatePo'])->name('summary_kanban.generate-po');
    Route::delete('summary-kanban/{id}', [\App\Http\Controllers\SummaryKanbanController::class, 'destroy'])->name('summary_kanban.destroy');
    Route::get('summary-kanban/export/excel', [\App\Http\Controllers\SummaryKanbanController::class, 'exportExcel'])->name('summary_kanban.excel');
    Route::get('summary-kanban/export/pdf', [\App\Http\Controllers\SummaryKanbanController::class, 'exportPdf'])->name('summary_kanban.pdf');
    Route::get('summary-kanban/demands/template', [\App\Http\Controllers\SummaryKanbanController::class, 'demandTemplate'])->name('summary_kanban.demands.template');
    Route::post('summary-kanban/demands/import', [\App\Http\Controllers\SummaryKanbanController::class, 'importDemands'])->name('summary_kanban.demands.import');
    Route::delete('summary-kanban/demands/clear', [\App\Http\Controllers\SummaryKanbanController::class, 'clearDemands'])->name('summary_kanban.demands.clear');
    Route::get('summary-kanban/pending-items', [\App\Http\Controllers\SummaryKanbanController::class, 'getPendingItems'])->name('summary_kanban.pending');
    Route::get('summary-kanban/rm-requirement', [\App\Http\Controllers\SummaryKanbanController::class, 'getRmRequirementPerFp'])->name('summary_kanban.rm_requirement');

    Route::get('stock-overviews', [\App\Http\Controllers\StockOverviewController::class, 'index'])->name('stock_overviews.index');
    Route::get('stock-overviews/export/excel', [\App\Http\Controllers\StockOverviewController::class, 'exportExcel'])->name('stock_overviews.export');

    Route::get('business-logs', [\App\Http\Controllers\BusinessEventLogController::class, 'index'])->name('business_logs.index');
    Route::get('business-logs/export/excel', [\App\Http\Controllers\BusinessEventLogController::class, 'exportExcel'])->name('business_logs.export');

    // ========== MOVEMENTS ==========
    Route::resource('goods-receipts', \App\Http\Controllers\GoodsReceiptController::class)->names('goods_receipts')->except(['create','edit','update']);
    Route::post('goods-receipts/update', [\App\Http\Controllers\GoodsReceiptController::class, 'update'])->name('goods_receipts.update');
    Route::get('goods-receipts/print-pdf', [\App\Http\Controllers\GoodsReceiptController::class, 'printPdf'])->name('goods_receipts.print_pdf');
    Route::get('goods-receipts/export/excel', [\App\Http\Controllers\GoodsReceiptController::class, 'exportExcel'])->name('goods_receipts.export');

    Route::resource('goods-issues', \App\Http\Controllers\GoodsIssueController::class)->names('goods_issues')->except(['edit','update']);
    Route::post('goods-issues/update', [\App\Http\Controllers\GoodsIssueController::class, 'update'])->name('goods_issues.update');
    Route::get('goods-issues/print-pdf', [\App\Http\Controllers\GoodsIssueController::class, 'printPdf'])->name('goods_issues.print_pdf');
    Route::get('goods-issues/export/excel', [\App\Http\Controllers\GoodsIssueController::class, 'exportExcel'])->name('goods_issues.export');

});

// ======================
// LOGISTIK
// ======================
Route::middleware(['auth', 'role:logistik'])->prefix('logistik')->group(function () {

    // Dashboard
    Route::get('/dashboard', function () { return redirect()->route('rundown_incoming.index'); })->name('logistik.dashboard');

    // ========== LOGISTIC & INCOMING ==========
    Route::get('rundown-incoming', [\App\Http\Controllers\RundownIncomingController::class, 'index'])->name('rundown_incoming.index');
    Route::post('rundown-incoming/upload', [\App\Http\Controllers\RundownIncomingController::class, 'upload'])->name('rundown_incoming.upload');
    Route::get('rundown-incoming/export', [\App\Http\Controllers\RundownIncomingController::class, 'export'])->name('rundown_incoming.export');
    Route::get('rundown-incoming/template', [\App\Http\Controllers\RundownIncomingController::class, 'downloadTemplate'])->name('rundown_incoming.template');
    Route::post('rundown-incoming/add', [\App\Http\Controllers\RundownIncomingController::class, 'addJob'])->name('rundown_incoming.add');
    Route::post('rundown-incoming/add-incoming', [\App\Http\Controllers\RundownIncomingController::class, 'addIncoming'])->name('rundown_incoming.add_incoming');
    Route::post('rundown-incoming/delete', [\App\Http\Controllers\RundownIncomingController::class, 'deleteJob'])->name('rundown_incoming.delete');
    Route::post('rundown-incoming/update-inline', [\App\Http\Controllers\RundownIncomingController::class, 'updateInline'])->name('rundown_incoming.inline');

    Route::get('pallet-mutation', [\App\Http\Controllers\PalletMutationController::class, 'index'])->name('pallet_mutation.index');

    // ========== SMR ==========
    Route::get('smr-vendor', [\App\Http\Controllers\SmrVendorController::class, 'index'])->name('smr_vendor.index');
    Route::get('smr-customer', [\App\Http\Controllers\SmrCustomerController::class, 'index'])->name('smr_customer.index');

    // ========== DATA FISIK ==========
    Route::get('data-gr', [\App\Http\Controllers\DataGrController::class, 'index'])->name('data_gr.index');
    Route::get('data-scrap', [\App\Http\Controllers\DataScrapController::class, 'index'])->name('data_scrap.index');

});

// ======================
// MANAGER
// ======================
Route::middleware(['auth', 'role:manager'])->prefix('manager')->name('manager.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Manager\DashboardController::class, 'index'])->middleware('feature:dashboard')->name('dashboard');
});

// ======================
// KADIV
// ======================
Route::middleware(['auth', 'role:kadiv'])->prefix('kadiv')->name('kadiv.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Kadiv\DashboardController::class, 'index'])->middleware('feature:dashboard')->name('dashboard');
});

// ======================
// DIREKTUR
// ======================
Route::middleware(['auth', 'role:direktur'])->prefix('direktur')->name('direktur.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Direktur\DashboardController::class, 'index'])->middleware('feature:dashboard')->name('dashboard');
});

// ======================
// PRESDIR
// ======================
Route::middleware(['auth', 'role:presdir'])->prefix('presdir')->name('presdir.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Presdir\DashboardController::class, 'index'])->middleware('feature:dashboard')->name('dashboard');
});

// ======================
// HAMBATAN JALUR
// ======================
Route::middleware(['auth', 'role:dies_shop,plant_service,irm,logistik,produksi,supervisor,admin,leader,shearing,handwork', 'feature:hambatan_jalur'])
    ->prefix('hambatan-jalur')
    ->name('hambatan-jalur.')
    ->controller(\App\Http\Controllers\HambatanJalurController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->name('show');
        Route::post('/{id}/sign', 'sign')->name('sign');
        Route::post('/{id}/leader-sign', 'leaderSign')->name('leader-sign');
    });

// ======================
// NOTIFICATIONS
// ======================
Route::middleware('auth')
    ->prefix('notifications')
    ->name('notifications.')
    ->controller(\App\Http\Controllers\NotificationController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/unread', 'unread')->name('unread');
        Route::post('/{id}/read', 'markRead')->name('read');
    });

// ======================
// ANALYTICS
// ======================
Route::middleware(['auth', 'feature:production_analytics'])
    ->prefix('analytics')
    ->name('analytics.')
    ->group(function () {
        Route::get('/production', [\App\Http\Controllers\Analytics\ProductionAnalyticsController::class, 'index'])->name('production');
        Route::get('/production/job/{id}', [\App\Http\Controllers\Analytics\ProductionAnalyticsController::class, 'jobDetail'])->name('production.job');
    });

// ======================
// PRODUCTION
// ======================
Route::middleware(['auth', 'role:production'])->prefix('production')->name('production.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Production\DashboardController::class, 'index'])->middleware('feature:dashboard')->name('dashboard');
});

// ======================
// SHARED GSPH API (accessible by all authenticated roles)
// ======================
Route::middleware(['auth'])->prefix('api')->name('api.')->group(function () {
    Route::get('/gsph', [\App\Http\Controllers\Api\GrafikController::class, 'gsph']);
});