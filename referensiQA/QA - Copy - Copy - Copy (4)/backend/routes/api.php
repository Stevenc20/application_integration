<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\QprController;
use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\LembarInspeksiController;
use App\Http\Controllers\Api\LiTemplateController;
use App\Http\Controllers\Api\IntercomController;

// ── Public ──
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Public TTD (tanpa login)
Route::get('sign/{token}',     [ApprovalController::class, 'getByToken']);
Route::post('sign/{token}',    [ApprovalController::class, 'sign']);
Route::get('qprs/{id}/tokens', [ApprovalController::class, 'getTokens']);

// Agent debug log (same-origin; browser blocks fetch to 127.0.0.1 from LAN IP)
if (app()->environment('local')) {
    Route::post('/agent-debug-log', function (Request $request) {
        $path = base_path('../debug-88c4d0.log');
        $line = json_encode($request->all(), JSON_UNESCAPED_UNICODE) . "\n";
        file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
        return response()->json(['ok' => true]);
    });
}

// Handle preflight OPTIONS
Route::options('{any}', fn() => response()->json([], 200))->where('any', '.*');

// Fallback untuk route 'login' agar tidak error 500 saat token expired/missing (Sanctum redirect)
// Note: Tidak pakai ->name('login') karena nama tersebut sudah dipakai di web.php
Route::get('/login', fn() => response()->json(['message' => 'Unauthenticated.'], 401));

// ── Protected ──
Route::middleware(['web', 'auth'])->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout',          [AuthController::class, 'logout']);
        Route::post('/logout-all',      [AuthController::class, 'logoutAll']);
        Route::get('/me',               [AuthController::class, 'me']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });
    
    // Signature PIN verification & retrieval
    Route::post('signature/verify-pin', [\App\Http\Controllers\Api\SignatureController::class, 'verifyPin']);
    Route::get('signature/get-signature', [\App\Http\Controllers\Api\SignatureController::class, 'getSignature']);
    Route::post('signature/save-master', [\App\Http\Controllers\Api\SignatureController::class, 'saveMaster']);

    // Users — by-role bisa diakses semua user login (untuk dropdown di form)
    Route::get('users/by-role',                [UserController::class, 'byRole']);

    // Users (admin only)
    Route::middleware('role:Admin')->group(function () {
        Route::get('users',                    [UserController::class, 'index']);
        Route::post('users',                   [UserController::class, 'store']);
        Route::get('users/{id}',               [UserController::class, 'show']);
        Route::put('users/{id}',               [UserController::class, 'update']);
        Route::delete('users/{id}',            [UserController::class, 'destroy']);

        Route::put('users/{id}/toggle-active', [UserController::class, 'toggleActive']);
    });

    // QPR — urutan penting: spesifik dulu sebelum wildcard {id}
    Route::prefix('qprs')->group(function () {
        Route::get('/pending-approval',          [QprController::class, 'pendingApproval']);
        Route::post('/upload-sketch',            [QprController::class, 'uploadSketch']);

        Route::get('/',                          [QprController::class, 'index']);
        Route::post('/',                         [QprController::class, 'store']);
        Route::patch('/draft',                   [QprController::class, 'saveDraft']);

        Route::get('/{id}',                      [QprController::class, 'show']);
        Route::put('/{id}',                      [QprController::class, 'update']);
        Route::patch('/{id}/draft',              [QprController::class, 'saveDraft']);
        Route::delete('/{id}',                   [QprController::class, 'destroy']);
        Route::post('/{id}/sign',                [QprController::class, 'sign']);
        Route::post('/{id}/revision',            [QprController::class, 'requestRevision']);
        Route::get('/{id}/signatures',           [QprController::class, 'signatures']);
        Route::post('/{id}/generate-tokens',     [ApprovalController::class, 'generateTokens']);
    });


    // Lembar Inspeksi
    Route::prefix('inspeksi')->group(function () {
        Route::get('/',                          [LembarInspeksiController::class, 'index']);
        Route::post('/',                         [LembarInspeksiController::class, 'store']);
        Route::get('/pending-ttd',               [LembarInspeksiController::class, 'pendingTtd']);
        Route::get('/search',                    [LembarInspeksiController::class, 'search']);
        Route::get('/rekap-bulanan',             [LembarInspeksiController::class, 'rekapBulanan']);
        Route::get('/leaderboard',               [LembarInspeksiController::class, 'leaderboard']);
        Route::post('/upload-sketch',            [LembarInspeksiController::class, 'uploadSketch']);

        Route::get('/{id}',                      [LembarInspeksiController::class, 'show']);
        Route::put('/{id}',                      [LembarInspeksiController::class, 'update']);
        Route::delete('/{id}',                   [LembarInspeksiController::class, 'destroy']);
        Route::post('/{id}/restore',             [LembarInspeksiController::class, 'restore']);
        Route::post('/{id}/sign',                [LembarInspeksiController::class, 'sign']);
        Route::post('/{id}/reject',              [LembarInspeksiController::class, 'reject']);
        Route::post('/{id}/sign-column',         [LembarInspeksiController::class, 'signColumn']);
        Route::post('/{id}/generate-qpr',        [LembarInspeksiController::class, 'generateQpr']);
        Route::post('/{id}/assign',              [LembarInspeksiController::class, 'assign']);
        Route::post('/{id}/claim',               [LembarInspeksiController::class, 'claim']);
        Route::post('/{id}/field-revisions',     [LembarInspeksiController::class, 'saveFieldRevisions']);
        Route::post('/{id}/resolve-revision',    [LembarInspeksiController::class, 'resolveFieldRevision']);
    });

    // Item Check
    Route::prefix('item-check')->group(function () {
        Route::get('/pending-ttd',               [\App\Http\Controllers\Api\ItemCheckController::class, 'pendingTtd']);
        Route::get('/search',                    [\App\Http\Controllers\Api\ItemCheckController::class, 'search']);
        Route::get('/summary',                   [\App\Http\Controllers\Api\ItemCheckController::class, 'summaryList']);
        Route::get('/{id}',                      [\App\Http\Controllers\Api\ItemCheckController::class, 'show']);
        Route::put('/{id}',                      [\App\Http\Controllers\Api\ItemCheckController::class, 'update']);
        Route::post('/{id}/start',               [\App\Http\Controllers\Api\ItemCheckController::class, 'start']);
        Route::post('/{id}/sign',                [\App\Http\Controllers\Api\ItemCheckController::class, 'sign']);
        Route::post('/{id}/assign',              [\App\Http\Controllers\Api\ItemCheckController::class, 'assign']);
        Route::post('/{id}/field-revisions',     [\App\Http\Controllers\Api\ItemCheckController::class, 'saveFieldRevisions']);
        Route::post('/{id}/resolve-revision',    [\App\Http\Controllers\Api\ItemCheckController::class, 'resolveFieldRevision']);
        Route::post('/{id}/resume',              [\App\Http\Controllers\Api\ItemCheckController::class, 'resumeTimer']);
    });

    // Production Schedules
    Route::prefix('production-schedules')->group(function () {
        Route::get('/{job_no}', [\App\Http\Controllers\Api\ProductionScheduleController::class, 'getByJobNo']);
    });

    // LI Templates
    Route::prefix('li-templates')->group(function () {
        Route::get('/',                          [LiTemplateController::class, 'index']);
        Route::post('/',                         [LiTemplateController::class, 'store']);
        Route::post('/sync-from-li',             [LiTemplateController::class, 'syncFromLi']);
        Route::get('/{part_no}',                 [LiTemplateController::class, 'showByPartNo']);
        Route::delete('/{part_no}',              [LiTemplateController::class, 'destroyByPartNo']);
    });




    // Intercom
    Route::prefix('intercom')->group(function () {
        Route::post('/call',                  [IntercomController::class, 'initiateCall']);
        Route::get('/status/{liId}',          [IntercomController::class, 'checkCallStatus']);
        Route::post('/respond',               [IntercomController::class, 'respondCall']);
        Route::post('/arrive',                [IntercomController::class, 'arriveAtLine']);
        Route::post('/complete/{liId}',       [IntercomController::class, 'completeCall']);
        Route::get('/active-incoming',        [IntercomController::class, 'checkActiveIncoming']);
    });
});