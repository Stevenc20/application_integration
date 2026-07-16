# Fix Break Time - Default dari Excel, bisa diubah via Master

## Ringkasan
Break time dari Excel harus jadi DEFAULT (tidak hilang saat upload ulang).
Hanya berubah jika Master Break Time diedit secara eksplisit.

## 1. `TimelineGenerationService.php`

### Method `regenerateSection()`
**Signature:**
```php
public function regenerateSection(string $date, string $shiftName, ?string $pressName = null, bool $forceMaster = false): int
```

**Logic perubahan:**
```php
// 1. Capture existing break rows SEBELUM dihapus
$capturedBreaks = ProductionPlan::whereDate('plan_date', $date)
    ->where('shift_name', $shiftName)
    ->where('row_type', 'break');
if ($pressName) {
    $this->applyPressFilter($capturedBreaks, $pressName);
}
$capturedBreaks = $capturedBreaks->orderBy('row_no')->get();

// 2. Hapus break lama (kode existing)
$previousBreaks->delete();

// 3. Tentukan break windows:
if ($forceMaster) {
    // Dari Master Break Time (saat user edit master)
    $breakWindows = $this->resolveBreakWindows($date, $shiftName, $hari);
} else {
    // Default: dari DB (Excel), fallback ke master, lalu hardcoded
    $breakWindows = $this->breakWindowsFromCaptured($capturedBreaks);
    if (empty($breakWindows)) {
        $breakWindows = $this->resolveBreakWindows($date, $shiftName, $hari);
    }
    if (empty($breakWindows)) {
        $breakWindows = $this->defaultBreakWindows($hari, $shiftName);
    }
}
```

### Method baru `breakWindowsFromCaptured()`
```php
private function breakWindowsFromCaptured(Collection $captured): array
{
    if ($captured->isEmpty()) {
        return [];
    }

    $windows = [];
    foreach ($captured as $b) {
        $startStr = substr((string) ($b->start_time ?? ''), 0, 5);
        $finishStr = substr((string) ($b->finish_time ?? ''), 0, 5);

        // VALIDASI: hanya HH:MM valid, skip 00:00, skip finish <= start
        if (!preg_match('/^\d{2}:\d{2}$/', $startStr) || !preg_match('/^\d{2}:\d{2}$/', $finishStr)) {
            continue;
        }
        if ($startStr === '00:00' && $finishStr === '00:00') {
            continue;
        }
        if (MasterBreakTime::timeToMinutes($startStr) >= MasterBreakTime::timeToMinutes($finishStr)) {
            continue;
        }

        // Percayai row_type='break' dari DB — label flexibel
        $label = strtoupper($b->job_no ?? $b->job_master ?? 'ISTIRAHAT');
        $isCinkorak = str_contains($label, 'CINGKORAK');

        $windows[] = [
            'start' => $startStr,
            'finish' => $finishStr,
            'type' => $isCinkorak ? 'cinkorak' : 'istirahat',
            'label' => $label,
        ];
    }

    return $windows;
}
```

### Method `regenerateAllSections()`
Tambah parameter `$forceMaster`:
```php
public function regenerateAllSections(bool $forceMaster = false): void
{
    $sections = ProductionPlan::select('plan_date', 'shift_name', 'press_name')
        ->whereNotNull('plan_date')
        ->whereNotNull('shift_name')
        ->distinct()
        ->get();

    foreach ($sections as $sec) {
        $this->regenerateSection(
            $sec->plan_date instanceof Carbon ? $sec->plan_date->toDateString() : (string)$sec->plan_date,
            (string)$sec->shift_name,
            $sec->press_name ? (string)$sec->press_name : null,
            $forceMaster
        );
    }
}
```

### Method `regenerateForPlan()`
```php
public function regenerateForPlan(ProductionPlan $plan, bool $forceMaster = false): void
{
    $dateStr = $plan->plan_date instanceof Carbon ? $plan->plan_date->toDateString() : (string)$plan->plan_date;
    $this->regenerateSection($dateStr, $plan->shift_name, $plan->press_name, $forceMaster);
}
```

## 2. `ScheduleTimelineService.php`
Update passthrough methods signature:
```php
public function regenerateSection(string $date, string $shiftName, ?string $pressName = null, bool $forceMaster = false): int
{
    return $this->generator->regenerateSection($date, $shiftName, $pressName, $forceMaster);
}
```

## 3. `BreaktimeController.php`
Cari method `store()`, `update()`, `destroy()` — panggil `$this->timelineGenerator->regenerateAllSections(true)`

## 4. `BreakTimeParameterController.php`
Cari method `store()`, `update()`, `toggle()` — panggil `$this->timelineGenerator->regenerateAllSections(true)`

## 5. `ProductionPlanController.php`
Hapus parameter `$excelBreakWindows` dari panggilan `regenerateSection()` — tidak diperlukan lagi karena capture dari DB otomatis.

### Method baru `defaultBreakWindows()`
Fallback terakhir jika capture DB dan master sama-sama kosong:
```php
private function defaultBreakWindows(?string $hari = null, string $shiftName = ''): array
{
    $isMalam = str_contains(strtolower($shiftName), 'malam') || str_contains($shiftName, '2');
    if ($isMalam) {
        return [
            ['start' => '00:00', 'finish' => '00:45', 'type' => 'istirahat', 'label' => 'ISTIRAHAT MALAM'],
            ['start' => '04:45', 'finish' => '05:00', 'type' => 'istirahat', 'label' => 'BREAKTIME'],
        ];
    }
    $windows = [];
    $windows[] = ['start' => '12:00', 'finish' => '12:45', 'type' => 'istirahat', 'label' => 'ISTIRAHAT SIANG'];
    $windows[] = ['start' => '15:15', 'finish' => '15:30', 'type' => 'cinkorak', 'label' => 'CINGKORAK'];
    $windows[] = ['start' => '16:30', 'finish' => '16:45', 'type' => 'istirahat', 'label' => 'BREAKTIME'];
    if (!$hari || strtolower($hari) !== 'jumat') {
        $windows[] = ['start' => '18:00', 'finish' => '18:30', 'type' => 'istirahat', 'label' => 'ISTIRAHAT SORE'];
    }
    return $windows;
}
```

## Catatan
- Semua caller lain (ReportController, InputHarianController, dll) TIDAK perlu diubah
- Mereka panggil `regenerateSection($date, $shift, $press)` — `$forceMaster` default `false` → capture DB
- Master Break Time controller panggil `regenerateAllSections(true)` → `$forceMaster = true` → langsung pakai master
