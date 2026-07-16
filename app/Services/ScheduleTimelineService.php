<?php

namespace App\Services;

use App\Models\ProductionPlan;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * @deprecated Display-only helpers. Timeline generation lives in TimelineGenerationService.
 */
class ScheduleTimelineService
{
    public function __construct(
        private TimelineGenerationService $generator
    ) {}

    /**
     * Passthrough — LKH should read production_plans directly; kept for backward compatibility.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildDisplaySchedule(
        Collection $plans,
        string $date,
        string $shiftName,
        ?string $hari = null
    ): array {
        $isShiftMalam = $this->isShiftMalam($shiftName);
        $rows = [];
        $displayNo = 0;

        foreach ($plans->sortBy(fn ($p) => $p->row_no ?? PHP_INT_MAX) as $plan) {
            if (($plan->row_type ?? 'job') === 'break') {
                $rows[] = [
                    'row_type' => 'break',
                    'plan' => $plan,
                    'plan_id' => $plan->id,
                    'break_label' => $this->resolveBreakLabel($plan),
                    'schedule_start' => $this->parsePlanTime($date, $plan->start_time, $isShiftMalam),
                    'schedule_finish' => $this->parsePlanTime($date, $plan->finish_time, $isShiftMalam),
                ];
                continue;
            }

            $displayNo++;
            $rows[] = [
                'row_type' => 'job',
                'plan' => $plan,
                'plan_id' => $plan->id,
                'display_no' => $displayNo,
                'schedule_start' => $this->parsePlanTime($date, $plan->start_time, $isShiftMalam),
                'schedule_finish' => $this->parsePlanTime($date, $plan->finish_time, $isShiftMalam),
                'tpt_plan' => ProductionMetricsService::planTptStored($plan),
                'plan_gsph' => ProductionMetricsService::planGsphStored($plan),
            ];
        }

        return $rows;
    }

    public function regenerateSection(string $date, string $shiftName, ?string $pressName = null, bool $forceMaster = false): array
    {
        return $this->generator->regenerateSection($date, $shiftName, $pressName, $forceMaster);
    }

    public function regenerateForPlan(ProductionPlan $plan, bool $forceMaster = false): void
    {
        $this->generator->regenerateForPlan($plan, $forceMaster);
    }

    public function shouldRegenerateOnField(string $field): bool
    {
        return in_array($field, [
            'plan', 'qty_plt', 'ct_detik', 'dct', 'reg_active', 'total_mesin', 'row_no',
        ], true);
    }

    public function resolveBreakWindows(string $date, string $shiftName, ?string $hari = null): array
    {
        return $this->generator->resolveBreakWindows($date, $shiftName, $hari);
    }

    private function isShiftMalam(string $shiftName): bool
    {
        $s = strtolower($shiftName);

        return str_contains($s, 'malam') || str_contains($s, '2');
    }

    private function parsePlanTime(string $date, ?string $timeStr, bool $isShiftMalam): ?Carbon
    {
        if (!$timeStr) {
            return null;
        }

        $timeStr = str_replace('.', ':', trim($timeStr));
        $dt = Carbon::parse($date . ' ' . substr($timeStr, 0, 8));
        if ($isShiftMalam && (int) $dt->format('H') < 12) {
            $dt->addDay();
        }

        return $dt;
    }

    private function resolveBreakLabel(ProductionPlan $plan): string
    {
        $label = strtoupper(trim($plan->job_no ?? $plan->job_master ?? 'ISTIRAHAT'));

        return $label ?: 'ISTIRAHAT';
    }
}
