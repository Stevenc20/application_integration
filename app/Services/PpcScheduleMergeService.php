<?php

namespace App\Services;

use App\Models\JobMaster;
use App\Models\LineMaster;
use App\Models\ProductionPlan;
use App\Models\ScheduleRevision;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PpcScheduleMergeService
{
    /**
     * Fields that hold production actuals/history. A schedule import must never
     * overwrite these on a plan that is already linked to production.
     */
    public const PROTECTED_FIELDS = [
        'ok',
        'repair',
        'reject',
        'total_pcs',
        'tpt_total',
        'act_start',
        'act_finish',
        'dt_menit',
        'status',
    ];

    /**
     * Merge imported PPC rows into existing plans for the given date.
     * Rows that are no longer listed by PPC are intentionally left untouched.
     */
    public function merge(array $sheets, string $parsedDate, ?int $userId = null, array $options = []): array
    {
        $lineMap = LineMaster::pluck('id', 'line_name')->toArray();
        $sheets = array_values($sheets);

        if (empty($sheets) || empty($lineMap)) {
            return $this->emptyResult();
        }

        return DB::transaction(function () use ($sheets, $parsedDate, $userId, $lineMap, $options) {
            /** @var Collection<int, ProductionPlan> $existingPlans */
            $existingPlans = ProductionPlan::whereDate('plan_date', $parsedDate)
                ->where('source_type', 'ppc')
                ->get();

            if ($existingPlans->isNotEmpty() && empty($options['skip_backup'])) {
                app(ScheduleBackupService::class)->captureSnapshot($parsedDate, 'pre_import', $userId, [
                    'shift_scope' => $options['shift_scope'] ?? 'all',
                    'source_filename' => $options['source_filename'] ?? null,
                ]);
            }

            $snapshotBefore = $existingPlans->map(fn ($plan) => $this->snapshotPlan($plan))->values()->all();

            $connectedJobs = $this->connectedJobsFor($existingPlans);
            $jobsByPlanId = $this->indexJobsByPlanId($connectedJobs);

            $indexes = $this->indexExistingPlans($existingPlans);

            $retainedPlanIds = [];
            $usedPlanIds = [];
            $deleted = [];
            $protectedFromDeletion = [];
            $added = [];
            $updated = [];
            $unchanged = [];
            $imported = 0;
            $importedSectionKeys = [];
            $importedJobKeys = [];

            $hasRevInGroup = [];
            foreach ($sheets as $sheetData) {
                $cleanShift = $this->cleanShift($sheetData['shift_name'] ?? '');
                $groupKey = $cleanShift.'||'.($sheetData['press_name'] ?? '');
                if (preg_match('/REV|REVISI/i', (string) ($sheetData['shift_name'] ?? ''))) {
                    $hasRevInGroup[$groupKey] = true;
                }
            }

            usort($sheets, function ($a, $b) {
                $aRev = (int) preg_match('/REV|REVISI/i', $a['shift_name'] ?? '');
                $bRev = (int) preg_match('/REV|REVISI/i', $b['shift_name'] ?? '');

                return $bRev <=> $aRev;
            });

            foreach ($sheets as $sheetData) {
                $originalShift = $sheetData['shift_name'] ?? '';
                $cleanShift = $this->cleanShift($originalShift);
                $groupKey = $cleanShift.'||'.($sheetData['press_name'] ?? '');
                $isRevSheet = (bool) preg_match('/REV|REVISI/i', $originalShift);

                if (! $isRevSheet && ! empty($hasRevInGroup[$groupKey])) {
                    continue;
                }

                $shiftName = $this->displayShift($originalShift);
                $pressName = $sheetData['press_name'];
                $lineId = $this->matchLineId($pressName, $lineMap);
                $importedSectionKeys[$this->sectionKeyFrom($cleanShift, $pressName)] = true;

                foreach ($sheetData['rows'] as $item) {
                    $rawJobMaster = $this->safeVal($item['job_master'] ?? null, '');
                    $rawJobNo = $this->safeVal($item['job_no'] ?? null, '');
                    $rawKeterangan = $this->safeVal($item['keterangan'] ?? null, '');
                    $upperJobMaster = strtoupper(trim((string) $rawJobMaster));
                    $upperJobNo = strtoupper(trim((string) $rawJobNo));
                    $upperKeterangan = strtoupper((string) $rawKeterangan);
                    $cleanStart = $this->cleanTime($this->safeVal($item['start_time'], ''));
                    $cleanFinish = $this->cleanTime($this->safeVal($item['finish_time'], ''));

                    $metaLabels = [
                        'TOTAL STROKE', 'TOTAL  STROKE', 'TOTAL TPT', 'TARGET GSPH', 'GSPH',
                        'GSPH TOTAL', 'GSPH ITEM', 'PLAN STROKE', 'TOTAL PCS', 'TOTAL PROD', 'TOTAL',
                        'PLAN',
                    ];

                    // Explicit deletion instruction is indicated in Keterangan (e.g. "Delete Plan Shift 1", "Delete (Material T/a)")
                    // or explicit Delete instruction marker with "PLAN" or specific delete reason.
                    $hasDeleteInstruction = str_contains($upperKeterangan, 'DELETE')
                        || str_contains($upperKeterangan, 'HAPUS')
                        || str_starts_with($upperJobMaster, 'DELETE PLAN')
                        || str_starts_with($upperJobNo, 'DELETE PLAN');

                    if ($hasDeleteInstruction) {
                        $targetJobNo = !str_starts_with($upperJobNo, 'DELETE') ? $cleanJobNoCandidate = strtoupper(trim((string) $rawJobNo)) : '';
                        $targetJobMaster = !str_starts_with($upperJobMaster, 'DELETE') ? strtoupper(trim((string) $rawJobMaster)) : '';

                        $planToDelete = null;
                        if ($targetJobNo !== '') {
                            $planToDelete = $this->popCandidatePlan($indexes['byJobNoQueue'], $cleanShift.'||'.$lineId.'||'.$targetJobNo, $usedPlanIds);
                        }
                        if (!$planToDelete && $targetJobMaster !== '') {
                            $planToDelete = $this->popCandidatePlan($indexes['byJobMasterQueue'], $cleanShift.'||'.$lineId.'||'.$targetJobMaster, $usedPlanIds);
                        }

                        if ($planToDelete) {
                            $usedPlanIds[(int) $planToDelete->id] = true;
                            if ($this->planHasProductionActivity($planToDelete)) {
                                $retainedPlanIds[] = $planToDelete->id;
                                $protectedFromDeletion[] = array_merge($this->snapshotPlan($planToDelete), [
                                    'reason' => 'Protected: already has production activity',
                                ]);
                                Log::info("[PPC IMPORT MERGE] Plan ID {$planToDelete->id} ({$planToDelete->job_no}) marked for deletion by revision but protected due to existing activity.");
                            } else {
                                $deletedSnapshot = array_merge($this->snapshotPlan($planToDelete), [
                                    'reason' => $rawKeterangan ?: 'Deleted via revision instruction',
                                ]);
                                $this->deleteUnstartedPlanAndJobMaster($planToDelete);
                                $deleted[] = $deletedSnapshot;
                                Log::info("[PPC IMPORT MERGE] Plan ID {$planToDelete->id} ({$planToDelete->job_no}) safely deleted via revision instruction: {$rawKeterangan}");
                            }
                        }
                        continue;
                    }

                    $isMeta = str_starts_with(trim((string) $rawJobMaster), ':')
                        || $upperJobMaster === 'JOB MASTER'
                        || $upperJobNo === 'JOB NO.'
                        || in_array($upperJobMaster, $metaLabels, true)
                        || in_array($upperJobNo, $metaLabels, true);

                    if ($isMeta) {
                        continue;
                    }

                    $planQty = (float) $this->safeVal($item['plan'], 0);
                    $ctDetik = (float) $this->safeVal($item['ct_detik'] ?? ($item['ct'] ?? 0), 0);
                    $processTimeRaw = (float) $this->safeVal($item['process_time'], 0);

                    $isSummaryPattern = str_contains($upperJobMaster, 'TOTAL FINISH')
                        || str_contains($upperJobNo, 'TOTAL FINISH')
                        || str_contains($upperJobMaster, 'TOTAL FNISH')
                        || str_contains($upperJobNo, 'TOTAL FNISH')
                        || (($item['row_type'] ?? null) === 'break'
                            && (str_contains($upperJobMaster, 'FINISH') || str_contains($upperJobNo, 'FINISH')
                                || str_contains($upperJobMaster, 'FNISH') || str_contains($upperJobNo, 'FNISH')));

                    $isBreakDesc = strtolower((string) ($item['row_type'] ?? '')) === 'break';
                    foreach (['BREAK', 'ISTIRAHAT', 'CINGKORAK', 'BREAKTIME', 'BREAK TIME', 'ISHOMA'] as $keyword) {
                        if (str_contains($upperJobNo, $keyword)
                            || str_contains($upperJobMaster, $keyword)
                            || str_contains($upperKeterangan, $keyword)) {
                            $isBreakDesc = true;
                            break;
                        }
                    }

                    $rowType = $item['row_type'] ?? 'job';
                    if ($isSummaryPattern) {
                        $rowType = 'total_finish';
                    } elseif ($isBreakDesc || $rowType === 'break') {
                        $rowType = 'break';
                        if ($cleanStart === '') {
                            continue;
                        }
                    } elseif ($planQty > 0 || $ctDetik > 0) {
                        $rowType = 'job';
                    } elseif ($planQty == 0 && $ctDetik == 0 && $processTimeRaw == 0) {
                        $rowType = 'note';
                    } else {
                        $rowType = 'job';
                    }

                    if ($rowType === 'note') {
                        continue;
                    }

                    $jobMasterValue = $rawJobMaster;
                    $jobNoValue = $rawJobNo;
                    if ($rowType === 'total_finish') {
                        $jobMasterValue = 'TOTAL FINISH';
                        $jobNoValue = 'TOTAL FINISH';
                    }

                    $cleanJobMaster = strtoupper(trim((string) $jobMasterValue));
                    $cleanJobNo = strtoupper(trim((string) $jobNoValue));
                    if ($rowType === 'break' && $cleanJobNo === '') {
                        $cleanJobNo = $cleanJobMaster;
                    }

                    $dedupKey = $cleanShift.'||'.$lineId.'||'.$cleanJobMaster.'||'.$cleanJobNo
                        .'||'.$cleanStart.'||'.$cleanFinish.'||'.$planQty;
                    if (isset($importedJobKeys[$dedupKey])) {
                        continue;
                    }
                    $importedJobKeys[$dedupKey] = true;

                    $finalRowNo = (int) $this->safeVal($item['row_no'], 0);

                    $rowAttributes = [
                        'line_master_id' => $lineId,
                        'plan_date' => $parsedDate,
                        'shift_name' => $shiftName,
                        'press_name' => $pressName,
                        'hari' => $sheetData['hari'] ?? null,
                        'tgl' => $sheetData['tgl'] ?? null,
                        'jam' => $sheetData['jam'] ?? null,
                        'revisi' => $sheetData['revisi'] ?? null,
                        'job_master' => $jobMasterValue,
                        'job_no' => $jobNoValue,
                        'row_no' => $finalRowNo,
                        'row_type' => $rowType,
                        'source_type' => 'ppc',
                        'type_plt' => $item['type_plt'] ?? null,
                        'qty_plt' => $this->safeVal($item['qty_plt'], 0),
                        'keb_mtl' => $this->safeVal($item['keb_mtl'], 0),
                        'total_plt' => $this->safeVal($item['total_plt'], 0),
                        'each_part' => $item['each_part'] ?? null,
                        'plan' => $planQty,
                        'ct_detik' => $ctDetik,
                        'process_time' => $rowType === 'job' ? $processTimeRaw : 0,
                        'reg_active' => $this->safeVal($item['reg_active'], 0),
                        'dct' => $this->safeVal($item['dct'], 0),
                        'mct' => $this->safeVal($item['mct'], 0),
                        'plan_dct' => $this->safeVal($item['plan_dct'], 0),
                        'tpt' => $this->safeVal($item['tpt'], 0),
                        'gsph_item' => $this->safeVal($item['gsph_item'] ?? ($item['gsph'] ?? 0), 0),
                        'start_time' => $cleanStart,
                        'finish_time' => $cleanFinish,
                        'keterangan' => $rawKeterangan !== ''
                            ? $rawKeterangan
                            : (($item['row_type'] ?? null) === 'break' ? $rawJobNo : null),
                        'total_mesin' => $this->safeVal($item['total_mesin'] ?? 1, 1),
                        'a1' => $this->safeVal($item['a1'] ?? 0, 0),
                        'a2' => $this->safeVal($item['a2'] ?? 0, 0),
                        'a3' => $this->safeVal($item['a3'] ?? 0, 0),
                        'a4' => $this->safeVal($item['a4'] ?? 0, 0),
                        'p1' => (float) $this->safeVal($item['a1'] ?? 0, 0) > 0,
                        'p2' => (float) $this->safeVal($item['a2'] ?? 0, 0) > 0,
                        'p3' => (float) $this->safeVal($item['a3'] ?? 0, 0) > 0,
                        'p4' => (float) $this->safeVal($item['a4'] ?? 0, 0) > 0,
                        'ok' => $this->safeVal($item['ok'] ?? 0, 0),
                        'repair' => 0,
                        'reject' => $this->safeVal($item['reject'] ?? 0, 0),
                        'dt_menit' => $this->safeVal($item['dt_menit'] ?? 0, 0),
                        'total_pcs' => $this->safeVal($item['total_pcs'] ?? 0, 0),
                        'tpt_total' => $this->safeVal($item['tpt_total'] ?? 0, 0),
                    ];

                    $matchKeyWithRow = $rowType === 'break'
                        ? $cleanShift.'||'.$lineId.'||break||'.$cleanJobNo.'||'.$cleanStart.'||'.$cleanFinish
                        : $cleanShift.'||'.$lineId.'||'.$cleanJobMaster.'||'.$cleanJobNo.'||'.(int) $finalRowNo;

                    $matchedPlan = $this->matchExistingPlan(
                        $indexes,
                        $rowType,
                        $matchKeyWithRow,
                        $cleanShift,
                        $lineId,
                        $cleanJobMaster,
                        $cleanJobNo,
                        $finalRowNo,
                        $cleanStart,
                        $usedPlanIds
                    );

                    if ($matchedPlan && isset($usedPlanIds[(int) $matchedPlan->id])) {
                        $matchedPlan = null;
                    }

                    if ($matchedPlan) {
                        $before = $this->snapshotPlan($matchedPlan);
                        $payload = $rowAttributes;
                        foreach (self::PROTECTED_FIELDS as $field) {
                            unset($payload[$field]);
                        }
                        $changed = $this->changedFields($matchedPlan, $payload);

                        if (! empty($changed)) {
                            $matchedPlan->update($payload);
                            $updated[] = [
                                'plan_id' => $matchedPlan->id,
                                'shift' => $shiftName,
                                'press' => $pressName,
                                'job_master' => $matchedPlan->job_master,
                                'job_no' => $matchedPlan->job_no,
                                'changed_fields' => $changed,
                                'before' => $before,
                                'after' => $this->snapshotPlan($matchedPlan->fresh()),
                            ];
                        } else {
                            $unchanged[] = $this->snapshotPlan($matchedPlan);
                        }

                        $retainedPlanIds[] = $matchedPlan->id;
                        $usedPlanIds[(int) $matchedPlan->id] = true;
                        $this->syncJobMasterSchedule($matchedPlan->fresh(), $jobsByPlanId, $parsedDate);
                    } else {
                        $newPlan = ProductionPlan::create(array_merge($rowAttributes, ['status' => 'pending']));
                        $retainedPlanIds[] = $newPlan->id;
                        $usedPlanIds[(int) $newPlan->id] = true;
                        $added[] = $this->snapshotPlan($newPlan);
                        $imported++;

                        continue;
                    }

                    $imported++;
                }
            }

            $notInRevision = $existingPlans
                ->whereNotIn('id', $retainedPlanIds)
                ->filter(fn ($plan) => isset($importedSectionKeys[$this->sectionKey($plan)]))
                ->map(fn ($plan) => array_merge($this->snapshotPlan($plan), [
                    'has_activity' => $this->planHasProductionActivity($plan),
                ]))
                ->values()
                ->all();

            $counts = [
                'added' => count($added),
                'updated' => count($updated),
                'unchanged' => count($unchanged),
                'deleted' => count($deleted),
                'protected_from_deletion' => count($protectedFromDeletion),
                'not_in_revision' => count($notInRevision),
                'rows_imported' => $imported,
            ];

            $this->storeRevision($parsedDate, $sheets, $snapshotBefore, [
                'mode' => 'merge',
                'counts' => $counts,
                'added' => $added,
                'updated' => $updated,
                'unchanged' => $unchanged,
                'deleted' => $deleted,
                'protected_from_deletion' => $protectedFromDeletion,
                'not_in_revision' => $notInRevision,
            ], $userId);

            if ($existingPlans->isEmpty() && $imported > 0 && empty($options['skip_backup'])) {
                app(ScheduleBackupService::class)->ensureInitialBackup(
                    $parsedDate,
                    $userId,
                    $options['source_filename'] ?? null
                );
            }

            DashboardRealtimeService::flushCache();

            return [
                'counts' => $counts,
                'added' => $added,
                'updated' => $updated,
                'unchanged' => $unchanged,
                'deleted' => $deleted,
                'protected_from_deletion' => $protectedFromDeletion,
                'not_in_revision' => $notInRevision,
                'preview' => [
                    'counts' => $counts,
                    'added' => $added,
                    'updated' => $updated,
                    'unchanged' => $unchanged,
                    'deleted' => $deleted,
                    'protected_from_deletion' => $protectedFromDeletion,
                    'not_in_revision' => $notInRevision,
                ],
            ];
        });
    }

    public function planHasProductionActivity(ProductionPlan $plan): bool
    {
        if (((float) $plan->ok + (float) $plan->repair + (float) $plan->reject) > 0) {
            return true;
        }

        if ($plan->act_start) {
            return true;
        }

        $status = strtolower((string) $plan->status);
        if (in_array($status, ['running', 'paused', 'complete', 'completed', 'finished', 'closed', 'done'], true)) {
            return true;
        }

        $jobMaster = $this->findLinkedJobMaster((int) $plan->id);

        return $jobMaster ? $this->jobHasActivity($jobMaster) : false;
    }

    public function jobHasActivity(JobMaster $jobMaster): bool
    {
        if ($jobMaster->started_at) {
            return true;
        }

        $status = strtolower((string) $jobMaster->status);
        if (in_array($status, ['running', 'paused', 'complete', 'completed', 'finished', 'closed', 'done'], true)) {
            return true;
        }

        if ($jobMaster->dailyProduction()->exists()) {
            return true;
        }
        if ($jobMaster->productionLogs()->exists()) {
            return true;
        }
        if ($jobMaster->productionSessions()->exists()) {
            return true;
        }
        if ($jobMaster->downtimes()->exists()) {
            return true;
        }
        if ($jobMaster->dandoriSessions()->exists()) {
            return true;
        }
        if ($jobMaster->dandoris()->exists()) {
            return true;
        }

        return false;
    }

    public function findLinkedJobMaster(int $planId): ?JobMaster
    {
        return JobMaster::where('job_number', 'LIKE', "%-{$planId}")
            ->get()
            ->first(fn (JobMaster $jobMaster) => str_ends_with((string) $jobMaster->job_number, "-{$planId}"));
    }

    private function popCandidatePlan(array &$queueMap, string $key, array $usedPlanIds): ?ProductionPlan
    {
        if (empty($queueMap[$key])) {
            return null;
        }

        while (!empty($queueMap[$key])) {
            $candidate = array_shift($queueMap[$key]);
            if ($candidate && !isset($usedPlanIds[(int) $candidate->id])) {
                return $candidate;
            }
        }

        return null;
    }

    private function deleteUnstartedPlanAndJobMaster(ProductionPlan $plan): void
    {
        $planId = (int) $plan->id;
        $jobMaster = $this->findLinkedJobMaster($planId);
        if ($jobMaster && !$this->jobHasActivity($jobMaster)) {
            $jobMaster->delete();
        }
        $plan->delete();
    }

    private function matchExistingPlan(
        array &$indexes,
        string $rowType,
        string $matchKeyWithRow,
        string $shiftName,
        ?int $lineId,
        string $cleanJobMaster,
        string $cleanJobNo,
        $finalRowNo,
        string $cleanStart,
        array $usedPlanIds
    ): ?ProductionPlan {
        if ($rowType === 'break') {
            $break = $indexes['byKey'][$matchKeyWithRow][0] ?? null;
            if ($break && !isset($usedPlanIds[(int) $break->id])) {
                return $break;
            }

            $breakLabelKey = $shiftName.'||'.$lineId.'||break||'.$cleanJobNo;
            $break = $this->popCandidatePlan($indexes['byBreakLabelQueue'], $breakLabelKey, $usedPlanIds);
            if ($break) {
                return $break;
            }

            return $this->firstUnique($indexes['byBreakLabel'], $breakLabelKey);
        }

        $primary = $indexes['byKey'][$matchKeyWithRow][0] ?? null;
        if ($primary && !isset($usedPlanIds[(int) $primary->id])) {
            return $primary;
        }

        if ($cleanJobNo !== '' && ! in_array($cleanJobNo, ['-', 'BREAK'], true)) {
            $noStartKey = $shiftName.'||'.$lineId.'||'.$cleanJobNo.'||'.$cleanStart;
            $match = $this->firstUnique($indexes['byJobNoStart'], $noStartKey);
            if ($match && !isset($usedPlanIds[(int) $match->id])) {
                return $match;
            }

            $noKey = $shiftName.'||'.$lineId.'||'.$cleanJobNo;
            $match = $this->firstUnique($indexes['byJobNo'], $noKey);
            if ($match && !isset($usedPlanIds[(int) $match->id])) {
                return $match;
            }
        }

        if ($cleanJobMaster !== '' && $cleanJobMaster !== 'BREAK') {
            $jobMasterKey = $shiftName.'||'.$lineId.'||'.$cleanJobMaster;
            $looseKey = $jobMasterKey.'||'.(int) $finalRowNo;
            $match = $this->firstUnique($indexes['byLooseKey'], $looseKey);
            if ($match && !isset($usedPlanIds[(int) $match->id])) {
                return $match;
            }

            $match = $this->firstUnique($indexes['byJobMaster'], $jobMasterKey);
            if ($match && !isset($usedPlanIds[(int) $match->id])) {
                return $match;
            }
        }

        return null;
    }

    private function firstUnique(array $map, ?string $key): ?ProductionPlan
    {
        if ($key === null || ! isset($map[$key]) || count($map[$key]) !== 1) {
            return null;
        }

        return $map[$key][0];
    }

    private function indexExistingPlans(Collection $existingPlans): array
    {
        $byKey = [];
        $byJobNo = [];
        $byJobNoQueue = [];
        $byJobNoStart = [];
        $byJobMaster = [];
        $byJobMasterQueue = [];
        $byLooseKey = [];
        $byBreakLabel = [];
        $byBreakLabelQueue = [];

        foreach ($existingPlans as $plan) {
            $cleanShift = $this->cleanShift($plan->shift_name);
            $lineId = $plan->line_master_id;
            $jobMaster = strtoupper(trim((string) $plan->job_master));
            $jobNo = strtoupper(trim((string) $plan->job_no));

            if ($plan->row_type === 'break') {
                $breakLabel = $jobNo !== '' ? $jobNo : $jobMaster;
                $breakKey = $cleanShift.'||'.$lineId.'||break||'.$breakLabel
                    .'||'.trim((string) $plan->start_time).'||'.trim((string) $plan->finish_time);
                $byKey[$breakKey][] = $plan;
                if ($breakLabel !== '') {
                    $byBreakLabel[$cleanShift.'||'.$lineId.'||break||'.$breakLabel][] = $plan;
                    $byBreakLabelQueue[$cleanShift.'||'.$lineId.'||break||'.$breakLabel][] = $plan;
                }

                continue;
            }

            $byKey[$cleanShift.'||'.$lineId.'||'.$jobMaster.'||'.$jobNo.'||'.(int) $plan->row_no][] = $plan;
            $byKey[$cleanShift.'||'.$lineId.'||'.$jobMaster.'||'.$jobNo][] = $plan;

            if ($jobNo !== '' && ! in_array($jobNo, ['-', 'TOTAL FINISH', 'TOTAL FNISH', 'FINISH'], true)) {
                $byJobNo[$cleanShift.'||'.$lineId.'||'.$jobNo][] = $plan;
                $byJobNoQueue[$cleanShift.'||'.$lineId.'||'.$jobNo][] = $plan;
                $byJobNoStart[$cleanShift.'||'.$lineId.'||'.$jobNo.'||'.trim((string) $plan->start_time)][] = $plan;
            }

            if ($jobMaster !== '') {
                $byJobMaster[$cleanShift.'||'.$lineId.'||'.$jobMaster][] = $plan;
                $byJobMasterQueue[$cleanShift.'||'.$lineId.'||'.$jobMaster][] = $plan;
                $byLooseKey[$cleanShift.'||'.$lineId.'||'.$jobMaster.'||'.(int) $plan->row_no][] = $plan;
            }
        }

        return compact(
            'byKey',
            'byJobNo',
            'byJobNoQueue',
            'byJobNoStart',
            'byJobMaster',
            'byJobMasterQueue',
            'byLooseKey',
            'byBreakLabel',
            'byBreakLabelQueue'
        );
    }

    private function syncJobMasterSchedule(ProductionPlan $plan, array &$jobsByPlanId, string $parsedDate): void
    {
        if ($plan->row_type !== 'job') {
            return;
        }

        $planId = (int) $plan->id;
        $jobMaster = $jobsByPlanId[$planId] ?? null;

        if (! $jobMaster) {
            return;
        }

        $identifier = $this->identifierFor($plan);
        $updates = [
            'target_qty' => (int) ($plan->plan ?? 0),
            'sequence_no' => $plan->row_no ?: ($jobMaster->sequence_no ?? 1),
            'capacity' => (int) ($plan->qty_plt ?? 0),
        ];

        if ($plan->start_time) {
            $updates['plan_start'] = Carbon::parse($parsedDate.' '.$plan->start_time)->startOfMinute();
        }
        if ($plan->finish_time) {
            $updates['plan_end'] = Carbon::parse($parsedDate.' '.$plan->finish_time)->startOfMinute();
        }
        if ((string) $jobMaster->job_name !== (string) $plan->job_master) {
            $updates['job_name'] = $plan->job_master;
        }
        if ((string) $jobMaster->line !== (string) $plan->press_name) {
            $updates['line'] = $plan->press_name;
        }

        if ((string) $jobMaster->job_number !== $identifier) {
            $conflict = JobMaster::where('job_number', $identifier)
                ->where('id', '!=', $jobMaster->id)
                ->first();

            if ($conflict) {
                if (! $this->jobHasActivity($conflict)) {
                    $conflict->delete();
                    $updates['job_number'] = $identifier;
                } else {
                    Log::warning('[PPC IMPORT MERGE] Job number conflict, linked job_master kept at '.$jobMaster->job_number
                        .' (imported identifier '.$identifier.', plan_id '.$planId.')');
                }
            } else {
                $updates['job_number'] = $identifier;
            }
        }

        $jobMaster->update($updates);
        $jobsByPlanId[$planId] = $jobMaster->fresh();
    }

    private function identifierFor(ProductionPlan $plan): string
    {
        $jobNo = trim((string) $plan->job_no);

        if ($jobNo === '') {
            $jobNo = 'AUTO-'.(Str::slug((string) $plan->job_master) ?: 'JOB');
        }

        return $jobNo.'-'.$plan->id;
    }

    private function connectedJobsFor(Collection $existingPlans): Collection
    {
        $planIds = $existingPlans->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (empty($planIds)) {
            return collect();
        }

        return JobMaster::where(function ($query) use ($planIds) {
            foreach ($planIds as $planId) {
                $query->orWhere('job_number', 'LIKE', "%-{$planId}");
            }
        })->get()->filter(function (JobMaster $jobMaster) use ($planIds) {
            return (bool) preg_match('/-(\d+)$/', (string) $jobMaster->job_number, $matches)
                && in_array((int) $matches[1], $planIds, true);
        })->values();
    }

    private function indexJobsByPlanId(Collection $jobs): array
    {
        $map = [];
        foreach ($jobs as $jobMaster) {
            if (preg_match('/-(\d+)$/', (string) $jobMaster->job_number, $matches)) {
                $map[(int) $matches[1]] = $jobMaster;
            }
        }

        return $map;
    }

    private function storeRevision(string $parsedDate, array $sheets, array $before, array $after, ?int $userId): void
    {
        if (! $userId) {
            return;
        }

        try {
            $shiftNames = collect($sheets)
                ->map(fn ($sheet) => $this->displayShift($sheet['shift_name'] ?? ''))
                ->filter()
                ->unique()
                ->implode(', ');

            ScheduleRevision::create([
                'plan_date' => $parsedDate,
                'shift_name' => $shiftNames,
                'action' => 'import',
                'snapshot_before' => ['mode' => 'merge', 'plans' => $before],
                'snapshot_after' => $after,
                'created_by' => $userId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Schedule revision (merge import) failed: '.$e->getMessage());
        }
    }

    private function changedFields(ProductionPlan $plan, array $payload): array
    {
        $changed = [];

        foreach ($payload as $field => $value) {
            if (in_array($field, self::PROTECTED_FIELDS, true)) {
                continue;
            }

            if (! $this->valuesEqual($plan->getAttribute($field), $value)) {
                $changed[] = $field;
            }
        }

        return $changed;
    }

    private function valuesEqual($current, $incoming): bool
    {
        if ($current instanceof \DateTimeInterface) {
            $current = $current->format('Y-m-d');
        }
        if ($incoming instanceof \DateTimeInterface) {
            $incoming = $incoming->format('Y-m-d');
        }
        if (is_bool($current)) {
            $current = (int) $current;
        }
        if (is_bool($incoming)) {
            $incoming = (int) $incoming;
        }
        if (is_numeric($current) && is_numeric($incoming)) {
            return (float) $current === (float) $incoming;
        }

        return (string) ($current ?? '') === (string) ($incoming ?? '');
    }

    private function snapshotPlan(ProductionPlan $plan): array
    {
        return [
            'plan_id' => $plan->id,
            'line_master_id' => $plan->line_master_id,
            'shift' => $this->displayShift($plan->shift_name),
            'press' => $plan->press_name,
            'row_no' => $plan->row_no,
            'row_type' => $plan->row_type,
            'job_master' => $plan->job_master,
            'job_no' => $plan->job_no,
            'plan' => (float) $plan->plan,
            'start_time' => $plan->start_time,
            'finish_time' => $plan->finish_time,
            'ok' => (float) $plan->ok,
            'repair' => (float) $plan->repair,
            'reject' => (float) $plan->reject,
            'status' => $plan->status,
            'source_type' => $plan->source_type,
        ];
    }

    private function sectionKey(ProductionPlan $plan): string
    {
        return $this->sectionKeyFrom($this->cleanShift($plan->shift_name), $plan->press_name);
    }

    private function sectionKeyFrom(string $shiftName, ?string $pressName): string
    {
        return $shiftName.'||'.$pressName;
    }

    private function cleanShift(?string $shiftName): string
    {
        $clean = strtoupper(trim((string) $shiftName));
        $clean = preg_replace('/\s*\(.*?REV.*?\)|\s*REV(?:ISI)?\b/i', '', $clean);
        $clean = preg_replace('/\bSHIFT\s*/i', '', $clean);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));

        if (str_contains($clean, 'MALAM') || str_contains($clean, 'NIGHT')) {
            return 'Malam';
        }

        return 'Pagi';
    }

    private function displayShift(?string $shiftName): string
    {
        $display = preg_replace('/[\(\s]REV.*|[\(\s]REVISI.*|\d+/i', '', (string) $shiftName);
        $display = trim(preg_replace('/\s+/', ' ', ucwords(strtolower((string) $display))));

        if ($display === '' || ! preg_match('/\bshift\b/i', $display)) {
            return 'Shift '.$this->cleanShift($shiftName);
        }

        return $display;
    }

    private function matchLineId(?string $pressName, array $lineMap): ?int
    {
        $pressName = trim((string) $pressName);
        if (! $pressName) {
            return null;
        }

        $normalized = preg_replace('/^PRESS\s+/i', '', $pressName);

        if (isset($lineMap[$pressName])) {
            return (int) $lineMap[$pressName];
        }
        if (isset($lineMap[$normalized])) {
            return (int) $lineMap[$normalized];
        }

        foreach ($lineMap as $lineName => $lineId) {
            $cleanLineName = preg_replace('/^PRESS\s+/i', '', $lineName);
            if (strcasecmp($cleanLineName, $normalized) === 0) {
                return (int) $lineId;
            }
        }

        return null;
    }

    private function safeVal($val, $default = 0)
    {
        return (is_null($val) || $val === '') ? $default : $val;
    }

    private function cleanTime(?string $time): string
    {
        $time = trim((string) $time);
        if ($time === '') {
            return '';
        }

        if (preg_match('/(\d{1,2}):(\d{2})/', $time, $matches)) {
            return str_pad($matches[1], 2, '0', STR_PAD_LEFT).':'.$matches[2];
        }

        return $time;
    }

    private function emptyResult(): array
    {
        $counts = [
            'added' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'not_in_revision' => 0,
            'rows_imported' => 0,
        ];

        return [
            'counts' => $counts,
            'added' => [],
            'updated' => [],
            'unchanged' => [],
            'not_in_revision' => [],
            'preview' => [
                'counts' => $counts,
                'added' => [],
                'updated' => [],
                'unchanged' => [],
                'not_in_revision' => [],
            ],
        ];
    }
}
