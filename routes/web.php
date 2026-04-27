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
Route::middleware(['auth','role:admin'])->prefix('admin')->group(function(){

    Route::get('/dashboard', [AdminDashboardController::class,'index'])
        ->name('admin.dashboard');

    Route::resource('/users', UserController::class);

});

// master
Route::prefix('master/job')->group(function () {

    Route::get('/', [JobController::class, 'index'])->name('master.job');

    Route::post('/store', [JobController::class, 'store'])->name('master.job.store');

    Route::put('/update/{id}', [JobController::class, 'update']);

    Route::get('/delete/{id}', [JobController::class, 'delete']);

    Route::get('/edit/{id}', [JobController::class, 'edit']);

});

// SUPERVISOR
Route::middleware(['auth','role:supervisor'])
->prefix('supervisor')
->group(function(){

    Route::get('/dashboard', [SupervisorDashboard::class, 'index'])
        ->name('supervisor.dashboard');

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

    // Supervisor only
    Route::middleware('role:supervisor')->group(function(){
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

        Route::get('/history', function(){
            return view('downtime.trouble_history');
        })->name('history');

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

    Route::post('/job/{id}/pause',
        [InputHarianController::class, 'pause']
    )->name('job.pause');

    Route::post('/job/{id}/resume',
        [InputHarianController::class, 'resume']
    )->name('job.resume');

    Route::post('/job/{id}/restart',
        [InputHarianController::class, 'restart']
    )->name('job.restart');

    Route::post('/job/{id}/finish',
        [InputHarianController::class, 'finish']
    )->name('job.finish');

    Route::get('/job/{id}/status',
        [InputHarianController::class, 'status']
    )->name('job.status');

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
        [DandoriController::class, 'show']
    )->name('dandori.show');

    // load card atas
    Route::get('/dandori/load-jobs',
        [DandoriController::class, 'loadJobs']
    )->name('dandori.loadJobs');

    // buka item
    Route::get('/dandori/open/{id}',
        [DandoriController::class, 'open']
    )->name('dandori.open');

    // start activity
    Route::post('/dandori/start',
        [DandoriController::class, 'start']
    )->name('dandori.start');

    // finish activity
    Route::post('/dandori/finish/{id}',
        [DandoriController::class, 'finish']
    )->name('dandori.finish');

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

Route::get('/production_line', function(){   
    return view('planning.production_line');
})->name('production_line');

Route::get('/production_plan', function(){   
    return view('planning.production_plan');
})->name('production_plan');


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