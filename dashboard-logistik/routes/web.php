<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StockController;
use App\Http\Controllers\RundownController;
use App\Http\Controllers\ChartController;
use App\Http\Controllers\SinglePartController;
use App\Http\Controllers\RundownPressController;
use App\Http\Controllers\ScheduleStampingController;

Route::get('/', [StockController::class, 'index'])->name('stock.index');
Route::post('/upload', [StockController::class, 'upload'])->name('stock.upload');
Route::get('/rundown-stock', [RundownController::class, 'index'])->name('rundown.index');
Route::get('/data-finish-chart', [ChartController::class, 'index'])->name('chart.index');
Route::get('/single-part', [SinglePartController::class, 'index'])->name('single_part.index');
Route::get('/single-part/export', [SinglePartController::class, 'export'])->name('single_part.export');
Route::delete('/single-part/delete', [SinglePartController::class, 'deleteJob'])->name('single_part.delete');
Route::post('/single-part/upload', [SinglePartController::class, 'upload'])->name('single_part.upload');
Route::post('/single-part/add', [SinglePartController::class, 'addJob'])->name('single_part.add');
Route::post('/single-part/add-incoming', [SinglePartController::class, 'addIncoming'])->name('single_part.add_incoming');
Route::post('/single-part/update-inline', [SinglePartController::class, 'updateInline'])->name('single_part.inline');

Route::get('/rundown-press', [RundownPressController::class, 'index'])->name('rundown_press.index');
Route::post('/rundown-press/upload', [RundownPressController::class, 'upload'])->name('rundown_press.upload');
Route::post('/rundown-press/update-inline', [RundownPressController::class, 'updateInline'])->name('rundown_press.inline');

Route::get('/schedule-stamping', [ScheduleStampingController::class, 'index'])->name('schedule_stamping.index');
Route::get('/schedule_stamping', [ScheduleStampingController::class, 'index']);
Route::get('/schedule-stamping/export', [ScheduleStampingController::class, 'export'])->name('schedule_stamping.export');
Route::post('/schedule-stamping/upload', [ScheduleStampingController::class, 'upload'])->name('schedule_stamping.upload');
Route::post('/schedule-stamping/add-breaks', [ScheduleStampingController::class, 'addStandardBreaks'])->name('schedule_stamping.add_breaks');
Route::post('/schedule_stamping/add-breaks', [ScheduleStampingController::class, 'addStandardBreaks']);
Route::post('/schedule-stamping/update-inline', [ScheduleStampingController::class, 'updateInline'])->name('schedule_stamping.inline');