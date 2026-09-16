<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\LoginWebController;
use App\Http\Controllers\Web\LembarInspeksiWebController;
use App\Http\Controllers\Web\QprWebController;
use App\Http\Controllers\Web\AdminWebController;
use App\Http\Controllers\Web\QCWebController;
use App\Http\Controllers\Web\ApprovalWebController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\QprController;
use App\Http\Controllers\Api\LembarInspeksiController;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [LoginWebController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginWebController::class, 'login']);
Route::post('/logout', [LoginWebController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function() {
    Route::get('/dashboard', function () {
        return view('dashboard');
    });

    Route::get('/li', [LembarInspeksiWebController::class, 'index'])->name('li.index');
    Route::get('/li/create', [LembarInspeksiWebController::class, 'create'])->name('li.create');
    Route::get('/li/summary', [LembarInspeksiWebController::class, 'summary'])->name('li.summary');
    Route::get('/li/rekap', [LembarInspeksiWebController::class, 'rekap'])->name('li.rekap');
    Route::get('/li/{id}/edit', [LembarInspeksiWebController::class, 'edit'])->name('li.edit');
    Route::get('/li/{id}/print', [LembarInspeksiWebController::class, 'print'])->name('li.print');

    Route::get('/qpr', [QprWebController::class, 'index'])->name('qpr.index');
    Route::get('/qpr/create', [QprWebController::class, 'create'])->name('qpr.create');
    Route::get('/qpr/registration', [QprWebController::class, 'registration'])->name('qpr.registration');
    Route::get('/qpr/{id}/edit', [QprWebController::class, 'edit'])->name('qpr.edit');
    Route::get('/qpr/{id}/preview', [QprWebController::class, 'preview'])->name('qpr.preview');

    Route::middleware(['role:Admin'])->group(function() {
        Route::get('/admin/users', [AdminWebController::class, 'users'])->name('admin.users');
        Route::get('/admin/machines', [AdminWebController::class, 'machines'])->name('admin.machines');
        Route::get('/admin/defects', [AdminWebController::class, 'defects'])->name('admin.defects');
    });

    Route::middleware(['role:Admin,Leader,Supervisor,Group Leader,Foreman,Operator,QC'])->group(function() {
        Route::get('/li/master-template', [LembarInspeksiWebController::class, 'masterTemplate'])->name('li.master-template');
    });

    // Item Check Routes
    Route::get('/item-check', [\App\Http\Controllers\Web\ItemCheckController::class, 'index'])->name('item-check.index');
    Route::post('/item-check/{scheduleId}/start', [\App\Http\Controllers\Web\ItemCheckController::class, 'start'])->name('item-check.start');
    Route::get('/item-check/{id}/form', [\App\Http\Controllers\Web\ItemCheckController::class, 'form'])->name('item-check.form');
    Route::middleware(['role:Admin,QC'])->group(function() {
        Route::get('/item-check/preview/{templateId}', [\App\Http\Controllers\Web\ItemCheckController::class, 'preview'])->name('item-check.preview');
    });
    Route::get('/item-check/{id}/print', [\App\Http\Controllers\Web\ItemCheckController::class, 'print'])->name('item-check.print');


    Route::middleware(['role:Operator,QC,Admin,Leader,Supervisor,Foreman,Group Leader'])->group(function() {
        Route::get('/qc/worklist', [QCWebController::class, 'worklist'])->name('qc.worklist');
        Route::get('/rapor-qc', [QCWebController::class, 'rapor'])->name('qc.rapor');
    });

    // Approval center has been removed completely
    // ── API ROUTES (Dibuat di web.php agar bisa baca Session Login) ──
    Route::prefix('api')->group(function() {
        // Users
        Route::get('users/by-role', [UserController::class, 'byRole']);
        Route::middleware(['role:Admin'])->group(function () {
            Route::get('users',                    [UserController::class, 'index']);
            Route::post('users',                   [UserController::class, 'store']);
            Route::get('users/{id}',               [UserController::class, 'show']);
            Route::put('users/{id}',               [UserController::class, 'update']);
            Route::delete('users/{id}',            [UserController::class, 'destroy']);
            Route::put('users/{id}/toggle-active', [UserController::class, 'toggleActive']);
        });

        // QPR
        Route::prefix('qprs')->group(function () {
            Route::get('/pending-approval', [QprController::class, 'pendingApproval']);
            Route::get('/',                 [QprController::class, 'index']);
            Route::post('/',                [QprController::class, 'store']);
            Route::get('/{id}',             [QprController::class, 'show']);
            Route::post('/{id}/sign',       [QprController::class, 'sign']);
        });

        // Lembar Inspeksi
        Route::prefix('inspeksi')->group(function () {
            Route::post('/import-excel',     [LembarInspeksiController::class, 'importExcel']);
            Route::get('/',                  [LembarInspeksiController::class, 'index']);
            Route::post('/',                 [LembarInspeksiController::class, 'store']);
            Route::get('/pending-ttd',       [LembarInspeksiController::class, 'pendingTtd']);
            Route::get('/leaderboard',       [LembarInspeksiController::class, 'leaderboard']);
            Route::get('/search',            [LembarInspeksiController::class, 'search']);  
            Route::post('/upload-sketch',    [LembarInspeksiController::class, 'uploadSketch']);  
            Route::get('/{id}',              [LembarInspeksiController::class, 'show']);
            Route::post('/{id}/sign',        [LembarInspeksiController::class, 'sign']);
            Route::put('/{id}',              [LembarInspeksiController::class, 'update']);        
            Route::delete('/{id}',           [LembarInspeksiController::class, 'destroy']);       
            Route::post('/{id}/reject',      [LembarInspeksiController::class, 'reject']);        
        });

        Route::middleware(['role:Admin'])->group(function () {
            Route::apiResource('machines', \App\Http\Controllers\Api\ProductionLineController::class);
            Route::apiResource('defects', \App\Http\Controllers\Api\DefectMasterController::class);
        });
    });
});

