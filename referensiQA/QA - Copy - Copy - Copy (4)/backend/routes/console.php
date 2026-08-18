<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwal untuk sistem arsip Lembar Inspeksi
// Soft archive setiap tanggal 1 jam 01:00 pagi
Schedule::command('li:archive-old')->monthlyOn(1, '01:00');

// Hard delete (purge + backup PDF) setiap tanggal 1 jam 02:00 pagi
Schedule::command('li:purge-old')->monthlyOn(1, '02:00');

// Sinkronisasi otomatis data produksi PPC/SAP ke QA setiap jam
Schedule::command('qa:sync-schedule')->hourly();
