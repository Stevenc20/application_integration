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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

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
     * Verify that all required PPC Revision Membership columns exist in the database.
     * Throws a descriptive RuntimeException if migrations have not been executed yet.
     */
    public function assertRevisionSchemaReady(): void
    {
        $required = [
            'production_plans' => ['ppc_revision_id', 'ppc_revision_active'],
            'job_masters' => ['ppc_revision_id', 'ppc_revision_active'],
            'schedule_revisions' => ['section_scope', 'source_filename'],
        ];

        $missing = [];
        foreach ($required as $table => $columns) {
            if (! Schema::hasTable($table)) {
                $missing[] = "{$table} (tabel belum tersedia)";
                continue;
            }
            foreach ($columns as $col) {
                if (! Schema::hasColumn($table, $col)) {
                    $missing[] = "{$table}.{$col}";
                }
            }
        }

        if (! empty($missing)) {
            throw new RuntimeException(
                'Schema database belum siap untuk PPC Revision Sync (kolom belum tersedia: '
                . implode(', ', $missing)
                . '). Jalankan migrasi terlebih dahulu (`php artisan migrate`).'
            );
        }
    }

    /**
     * Merge imported PPC rows into existing plans for the given date.
     *
     * The latest revision is authoritative for every imported date+shift+press section.
     * Rows omitted from that section keep their history/actuals but are marked inactive
     * so they can never reappear in an active operational queue.
     */
    public function merge(array $sheets, string $parsedDate, ?int $userId = null, array $options = []): array
    {
        $this->assertRevisionSchemaReady();

        $lineMap = LineMaster::pluck('id', 'line_name')->toArray();
        $sheets = array_values($sheets);

        if (empty($sheets) || empty($lineMap)) {
            return $this->emptyResult();
        }

        return DB::transaction(function () use ($sheets, $parsedDate, $userId, $lineMap, $options) {
            /** @var Collection<int, ProductionPlan> $existingPlans */
            $existingPlans = ProductionPlan::whereDate('plan_date', $parsedDate)
                ->where('source_type', 'ppc')
                ->orderBy('row_no')
                ->orderBy('id')
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

            $sectionScope = [];
            foreach ($sheets as $sheetData) {
                $cleanShift = $this->cleanShift($sheetData['shift_name'] ?? '');
                $pressName = trim((string) ($sheetData['press_name'] ?? ''));
                $sectionScope[$this->sectionKeyFrom($cleanShift, $pressName)] = [
                    'shift' => $cleanShift,
                    'press' => $pressName,
                ];
            }

            $revision = $this->storeRevision($parsedDate, $sheets, $snapshotBefore, [], $userId, [
                'section_scope' => array_values($sectionScope),
                'source_filename' => $options['source_filename'] ?? null,
                'status' => 'processing',
                'backup_available' => $existingPlans->isNotEmpty(),
            ]);
            $revisionId = $revision?->id;

            $retainedPlanIds = [];
            $usedPlanIds = [];
            $deleted = [];
            $deletedPlanIds = [];
            $protectedFromDeletion = [];
            $blockedChanges = [];
            $added = [];
            $updated = [];
            $unchanged = [];
            $imported = 0;
            $importedSectionKeys = [];
            $importedJobKeys = [];
            $preparedSections = [];

            $hasRevInGroup = [];
            foreach ($sheets as $sheetData) {
                $cleanShift = $this->cleanShift($sheetData['shift_name'] ?? '');
                $groupKey = $this->sectionKeyFrom($cleanShift, $sheetData['press_name'] ?? '');
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
                $groupKey = $this->sectionKeyFrom($cleanShift, $sheetData['press_name'] ?? '');
                $isRevSheet = (bool) preg_match('/REV|REVISI/i', $originalShift);

                if (! $isRevSheet && ! empty($hasRevInGroup[$groupKey])) {
                    continue;
                }

                $shiftName = $this->displayShift($originalShift);
                $pressName = $sheetData['press_name'];
                $lineId = $this->matchLineId($pressName, $lineMap);
                $sectionKey = $this->sectionKeyFrom($cleanShift, $pressName);
                $importedSectionKeys[$sectionKey] = true;
                $sectionRetainedPlans = [];

                if (! isset($preparedSections[$sectionKey])) {
                    $preparedSections[$sectionKey] = true;
                }

                $currentMatchedRoot = null;
                $sawBreakSinceLastRow = false;
                $sheetRows = array_values($sheetData['rows'] ?? []);
                $reservedPlanIds = $this->preReserveExactRowMatches(
                    $sheetRows,
                    $indexes,
                    $cleanShift,
                    $lineId
                );

                foreach ($sheetRows as $rowIdx => $item) {
                    $rawJobMaster = $this->safeVal($item['job_master'] ?? null, '');
                    $rawJobNo = $this->safeVal($item['job_no'] ?? null, '');
                    $rawKeterangan = $this->safeVal($item['keterangan'] ?? null, '');
                    $upperJobMaster = strtoupper(trim((string) $rawJobMaster));
                    $upperJobNo = strtoupper(trim((string) $rawJobNo));
                    $upperKeterangan = strtoupper((string) $rawKeterangan);
                    $cleanStart = $this->cleanTime($this->safeVal($item['start_time'] ?? null, ''));
                    $cleanFinish = $this->cleanTime($this->safeVal($item['finish_time'] ?? null, ''));

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
                        $targetJobNo = ! str_starts_with($upperJobNo, 'DELETE') ? strtoupper(trim((string) $rawJobNo)) : '';
                        $targetJobMaster = ! str_starts_with($upperJobMaster, 'DELETE') ? strtoupper(trim((string) $rawJobMaster)) : '';

                        $planToDelete = $this->popDeleteTargetPlan(
                            $indexes,
                            $cleanShift,
                            $lineId,
                            $targetJobNo,
                            $targetJobMaster,
                            $usedPlanIds
                        );

                        if ($planToDelete) {
                            $rootToDelete = $planToDelete->resolveRootPlan($existingPlans);
                            $slicesToDelete = $rootToDelete->getScheduleGroupPlans($existingPlans);
                            if ($slicesToDelete->isEmpty()) {
                                $slicesToDelete = collect([$planToDelete]);
                            }

                            $groupHasActivity = $slicesToDelete->contains(
                                fn (ProductionPlan $slice) => $this->planHasProductionActivity($slice)
                            );

                            foreach ($slicesToDelete as $slice) {
                                $usedPlanIds[(int) $slice->id] = true;
                                if ($groupHasActivity) {
                                    $slice->update([
                                        'ppc_revision_id' => $revisionId,
                                        'ppc_revision_active' => false,
                                    ]);
                                    $this->markLinkedJobMastersMembership($slice, false, $revisionId);
                                    $retainedPlanIds[] = $slice->id;
                                    $protectedFromDeletion[] = array_merge($this->snapshotPlan($slice), [
                                        'reason' => 'Protected: already has production activity',
                                        'revision_active' => false,
                                    ]);
                                    Log::info("[PPC IMPORT MERGE] Plan ID {$slice->id} ({$slice->job_no}) marked for deletion by revision but protected due to existing activity.");
                                } else {
                                    $deletedSnapshot = array_merge($this->snapshotPlan($slice), [
                                        'reason' => $rawKeterangan ?: 'Deleted via revision instruction',
                                    ]);
                                    $this->deleteUnstartedPlanAndJobMaster($slice);
                                    $deletedPlanIds[] = (int) $slice->id;
                                    $deleted[] = $deletedSnapshot;
                                    Log::info("[PPC IMPORT MERGE] Plan ID {$slice->id} ({$slice->job_no}) safely deleted via revision instruction: {$rawKeterangan}");
                                }
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

                    $planQty = (float) $this->safeVal($item['plan'] ?? null, 0);
                    $ctDetik = (float) $this->safeVal($item['ct_detik'] ?? ($item['ct'] ?? 0), 0);
                    $processTimeRaw = (float) $this->safeVal($item['process_time'] ?? null, 0);
                    $dctRaw = (float) $this->safeVal($item['dct'] ?? null, 0);
                    $regActiveRaw = (float) $this->safeVal($item['reg_active'] ?? null, 0);

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

                    $hasCancelOrDeferNote = (bool) preg_match(
                        '/\b(BATAL|CANCEL|TUNDA|HOLD|OFF|STOP|PROSES\s+SHIFT|PINDAH\s+SHIFT|GESER\s+SHIFT)\b/i',
                        $upperKeterangan
                    );
                    $hasPlannedWindow = ($cleanStart !== '' && $cleanFinish !== '');
                    $hasDandoriSlot = ($dctRaw > 0 || $regActiveRaw > 0) && $hasPlannedWindow;

                    $rowType = $item['row_type'] ?? 'job';
                    if ($isSummaryPattern) {
                        $rowType = 'total_finish';
                    } elseif ($isBreakDesc || $rowType === 'break') {
                        $rowType = 'break';
                        if ($cleanStart === '') {
                            continue;
                        }
                    } elseif ($planQty > 0) {
                        $rowType = 'job';
                    } elseif ($planQty <= 0) {
                        // PLAN = 0: CT from Excel VLOOKUP alone must NEVER turn an unscheduled / cancelled row into an active job.
                        // A PLAN = 0 row is only a valid job slice when it is an explicit pre-break Dandori/setup slot
                        // with both START & FINISH times and non-zero DCT/RegActive, and without a cancellation/deferral note.
                        if (! $hasCancelOrDeferNote && $hasDandoriSlot) {
                            $rowType = 'job';
                        } else {
                            $rowType = 'note';
                        }
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

                    $finalRowNo = (int) $this->safeVal($item['row_no'] ?? null, 0);

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
                        'ppc_revision_id' => $revisionId,
                        'ppc_revision_active' => true,
                        'type_plt' => $item['type_plt'] ?? null,
                        'qty_plt' => $this->safeVal($item['qty_plt'] ?? null, 0),
                        'keb_mtl' => $this->safeVal($item['keb_mtl'] ?? null, 0),
                        'total_plt' => $this->safeVal($item['total_plt'] ?? null, 0),
                        'each_part' => $item['each_part'] ?? null,
                        'plan' => $planQty,
                        'ct_detik' => $ctDetik,
                        'process_time' => $rowType === 'job' ? $processTimeRaw : 0,
                        'reg_active' => $regActiveRaw,
                        'dct' => $dctRaw,
                        'mct' => $this->safeVal($item['mct'] ?? null, 0),
                        'plan_dct' => $this->safeVal($item['plan_dct'] ?? null, 0),
                        'tpt' => $this->safeVal($item['tpt'] ?? null, 0),
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

                    if ($rowType === 'break') {
                        $sawBreakSinceLastRow = true;
                    } elseif ($rowType !== 'job') {
                        $currentMatchedRoot = null;
                        $sawBreakSinceLastRow = false;
                    }

                    $isBreakSplitContinuationRow = false;
                    if ($rowType === 'job' && $sawBreakSinceLastRow && $currentMatchedRoot !== null) {
                        $prevJn = strtoupper(trim((string) ($currentMatchedRoot->job_no ?? '')));
                        $prevJm = strtoupper(trim((string) ($currentMatchedRoot->job_master ?? '')));
                        $sameIdentity = ($cleanJobNo !== '' && $cleanJobNo === $prevJn)
                            && ($cleanJobMaster === '' || $prevJm === '' || $cleanJobMaster === $prevJm);
                        if ($sameIdentity) {
                            $prePlanQty = (float) ($currentMatchedRoot->plan ?? 0);
                            if (($dctRaw <= 0 && $regActiveRaw <= 0) || ($prePlanQty <= 0 && $planQty > 0)) {
                                $isBreakSplitContinuationRow = true;
                            }
                        }
                    }

                    $incomingIndependentRank = 0;
                    $incomingIndependentTotal = 1;
                    if ($rowType === 'job' && ! $isBreakSplitContinuationRow) {
                        [$incomingIndependentRank, $incomingIndependentTotal] = $this->incomingRootRank(
                            $sheetRows,
                            $rowIdx,
                            $cleanJobMaster,
                            $cleanJobNo
                        );
                    }

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
                        $usedPlanIds,
                        $isBreakSplitContinuationRow ? $currentMatchedRoot : null,
                        $reservedPlanIds,
                        $incomingIndependentRank,
                        $incomingIndependentTotal
                    );

                    if ($matchedPlan && isset($usedPlanIds[(int) $matchedPlan->id])) {
                        $matchedPlan = null;
                    }

                    if ($matchedPlan) {
                        $before = $this->snapshotPlan($matchedPlan);
                        $wasActive = $matchedPlan->isActivePpcRevision();
                        $payload = $rowAttributes;

                        foreach (self::PROTECTED_FIELDS as $field) {
                            unset($payload[$field]);
                        }

                        if ($wasActive) {
                            // Keep existing membership values for an already-active row so
                            // re-uploading the same sheet remains idempotent. A legacy NULL
                            // marker is kept as active rather than rewritten on every import.
                            unset($payload['ppc_revision_id'], $payload['ppc_revision_active']);
                        }

                        $lockState = $this->scheduleLockState($matchedPlan);
                        $allowActiveMetadata = $lockState === 'running'
                            && ! empty($options['allow_active_metadata_update']);

                        if ($lockState !== null && ! $allowActiveMetadata) {
                            foreach (['plan', 'start_time', 'finish_time'] as $field) {
                                if (array_key_exists($field, $payload)
                                    && ! $this->valuesEqual($matchedPlan->getAttribute($field), $payload[$field])) {
                                    $blockedChanges[] = [
                                        'plan_id' => $matchedPlan->id,
                                        'shift' => $shiftName,
                                        'press' => $pressName,
                                        'job_master' => $matchedPlan->job_master,
                                        'job_no' => $matchedPlan->job_no,
                                        'lock_state' => $lockState,
                                        'field' => $field,
                                        'before' => $matchedPlan->getAttribute($field),
                                        'after' => $payload[$field],
                                        'reason' => 'Schedule item already has production activity; metadata change blocked.',
                                    ];
                                }
                                unset($payload[$field]);
                            }
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
                        $freshMatched = $matchedPlan->fresh();
                        $sectionRetainedPlans[] = $freshMatched;

                        if ($rowType === 'job') {
                            if (! $isBreakSplitContinuationRow) {
                                $currentMatchedRoot = $freshMatched->resolveRootPlan($existingPlans);
                            }
                            $sawBreakSinceLastRow = false;
                        }

                        $this->syncJobMasterSchedule(
                            $freshMatched,
                            $jobsByPlanId,
                            $parsedDate,
                            null,
                            ! empty($options['allow_active_metadata_update'])
                        );
                    } else {
                        $newPlan = ProductionPlan::create(array_merge($rowAttributes, ['status' => 'pending']));
                        $retainedPlanIds[] = $newPlan->id;
                        $usedPlanIds[(int) $newPlan->id] = true;
                        $sectionRetainedPlans[] = $newPlan;
                        $added[] = $this->snapshotPlan($newPlan);
                        $imported++;

                        if ($rowType === 'job') {
                            if (! $isBreakSplitContinuationRow) {
                                $currentMatchedRoot = $newPlan;
                            }
                            $sawBreakSinceLastRow = false;
                        }

                        continue;
                    }

                    $imported++;
                }

                // ONE SCHEDULE = ONE JOB IDENTITY:
                // Link break-split continuations in the imported section to their root schedule
                $this->linkBreakSplitContinuations(
                    $sectionRetainedPlans,
                    $jobsByPlanId,
                    $parsedDate,
                    ! empty($options['allow_active_metadata_update'])
                );
            }

            $notInRevision = $existingPlans
                ->whereNotIn('id', $retainedPlanIds)
                ->whereNotIn('id', $deletedPlanIds)
                ->filter(fn ($plan) => isset($importedSectionKeys[$this->sectionKey($plan)]))
                ->map(function (ProductionPlan $plan) use ($revisionId) {
                    $hasActivity = $this->planHasProductionActivity($plan);
                    $plan->update([
                        'ppc_revision_id' => $revisionId,
                        'ppc_revision_active' => false,
                    ]);
                    $this->markLinkedJobMastersMembership($plan, false, $revisionId);

                    return array_merge($this->snapshotPlan($plan->fresh()), [
                        'has_activity' => $hasActivity,
                        'revision_active' => false,
                    ]);
                })
                ->values()
                ->all();

            $counts = [
                'added' => count($added),
                'updated' => count($updated),
                'unchanged' => count($unchanged),
                'deleted' => count($deleted),
                'protected_from_deletion' => count($protectedFromDeletion),
                'blocked_changes' => count($blockedChanges),
                'not_in_revision' => count($notInRevision),
                'rows_imported' => $imported,
            ];

            $revisionAfter = [
                'mode' => 'merge',
                'status' => 'completed',
                'revision_id' => $revisionId,
                'counts' => $counts,
                'added' => $added,
                'updated' => $updated,
                'unchanged' => $unchanged,
                'deleted' => $deleted,
                'protected_from_deletion' => $protectedFromDeletion,
                'blocked_changes' => $blockedChanges,
                'not_in_revision' => $notInRevision,
            ];

            $this->finalizeRevision($revision, $revisionAfter, $parsedDate, $sheets, $snapshotBefore, $userId, [
                'section_scope' => array_values($sectionScope),
                'source_filename' => $options['source_filename'] ?? null,
                'backup_available' => $existingPlans->isNotEmpty(),
            ]);

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
                'blocked_changes' => $blockedChanges,
                'not_in_revision' => $notInRevision,
                'revision_id' => $revisionId,
                'preview' => [
                    'counts' => $counts,
                    'added' => $added,
                    'updated' => $updated,
                    'unchanged' => $unchanged,
                    'deleted' => $deleted,
                    'protected_from_deletion' => $protectedFromDeletion,
                    'blocked_changes' => $blockedChanges,
                    'not_in_revision' => $notInRevision,
                    'revision_id' => $revisionId,
                ],
            ];
        });
    }

    public function planHasProductionActivity(ProductionPlan $plan): bool
    {
        if (((float) $plan->ok + (float) $plan->repair + (float) $plan->reject) > 0) {
            return true;
        }

        if ($plan->act_start || $plan->act_finish) {
            return true;
        }

        $status = strtolower((string) $plan->status);
        if (in_array($status, ['running', 'paused', 'complete', 'completed', 'finished', 'closed', 'done'], true)) {
            return true;
        }

        $jobMaster = $this->findLinkedJobMaster((int) $plan->id);
        if ($jobMaster && $this->jobHasActivity($jobMaster)) {
            return true;
        }

        if ($plan->isChildSlice() && $plan->parent_job_id) {
            $rootJobMaster = $this->findLinkedJobMaster((int) $plan->parent_job_id);
            if ($rootJobMaster && $this->jobHasActivity($rootJobMaster)) {
                return true;
            }
        }

        return false;
    }

    public function jobHasActivity(JobMaster $jobMaster): bool
    {
        if ($jobMaster->started_at || $jobMaster->finished_at) {
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
        if ($jobMaster->repairRejects()->exists()) {
            return true;
        }

        return false;
    }

    public function findLinkedJobMaster(int $planId): ?JobMaster
    {
        return $this->findLinkedJobMasters($planId)->first();
    }

    public function findLinkedJobMasters(int $planId): Collection
    {
        return JobMaster::where('job_number', 'LIKE', "%-{$planId}")
            ->get()
            ->filter(fn (JobMaster $jobMaster) => str_ends_with((string) $jobMaster->job_number, "-{$planId}"))
            ->values();
    }

    private function markLinkedJobMastersMembership(ProductionPlan $plan, bool $active, ?int $revisionId): void
    {
        foreach ($this->findLinkedJobMasters((int) $plan->id) as $jobMaster) {
            $jobMaster->update([
                'ppc_revision_id' => $revisionId,
                'ppc_revision_active' => $active,
            ]);
        }
    }

    private function scheduleLockState(ProductionPlan $plan): ?string
    {
        if ($plan->row_type !== 'job' || ! $this->planHasProductionActivity($plan)) {
            return null;
        }

        $status = strtolower((string) $plan->status);
        if (in_array($status, ['complete', 'completed', 'finished', 'closed', 'done'], true)) {
            return 'completed';
        }

        $jobMaster = $this->findLinkedJobMaster((int) $plan->id);
        if (! $jobMaster && $plan->isChildSlice() && $plan->parent_job_id) {
            $jobMaster = $this->findLinkedJobMaster((int) $plan->parent_job_id);
        }

        if ($jobMaster) {
            $jobStatus = strtolower((string) $jobMaster->status);
            if (in_array($jobStatus, ['complete', 'completed', 'finished', 'closed', 'done'], true) || $jobMaster->finished_at) {
                return 'completed';
            }
        }

        if ($plan->act_finish) {
            return 'completed';
        }

        return 'running';
    }

    private function popDeleteTargetPlan(
        array &$indexes,
        string $cleanShift,
        ?int $lineId,
        string $targetJobNo,
        string $targetJobMaster,
        array $usedPlanIds
    ): ?ProductionPlan {
        if ($targetJobNo !== '' && $targetJobMaster !== '') {
            $pairKey = $cleanShift.'||'.$lineId.'||'.$targetJobMaster.'||'.$targetJobNo;
            $match = $this->popCandidatePlan($indexes['byRootIdentityQueue'], $pairKey, $usedPlanIds)
                ?? $this->popCandidatePlan($indexes['byIdentityQueue'], $pairKey, $usedPlanIds);
            if ($match) {
                return $match;
            }
        }

        if ($targetJobNo !== '') {
            $noKey = $cleanShift.'||'.$lineId.'||'.$targetJobNo;
            $match = $this->popCandidatePlan($indexes['byRootJobNoQueue'], $noKey, $usedPlanIds)
                ?? $this->popCandidatePlan($indexes['byJobNoQueue'], $noKey, $usedPlanIds);
            if ($match) {
                return $match;
            }
        }

        if ($targetJobMaster !== '') {
            $jmKey = $cleanShift.'||'.$lineId.'||'.$targetJobMaster;
            $match = $this->popCandidatePlan($indexes['byRootJobMasterQueue'], $jmKey, $usedPlanIds)
                ?? $this->popCandidatePlan($indexes['byJobMasterQueue'], $jmKey, $usedPlanIds);
            if ($match) {
                return $match;
            }
        }

        return null;
    }

    private function popCandidatePlan(array &$queueMap, string $key, array $usedPlanIds): ?ProductionPlan
    {
        if (empty($queueMap[$key])) {
            return null;
        }

        while (! empty($queueMap[$key])) {
            $candidate = array_shift($queueMap[$key]);
            if ($candidate && ! isset($usedPlanIds[(int) $candidate->id])) {
                return $candidate;
            }
        }

        return null;
    }

    private function deleteUnstartedPlanAndJobMaster(ProductionPlan $plan): void
    {
        $planId = (int) $plan->id;
        $jobMaster = $this->findLinkedJobMaster($planId);
        if ($jobMaster && ! $this->jobHasActivity($jobMaster)) {
            $jobMaster->delete();
        }
        $plan->delete();
    }

    private function preReserveExactRowMatches(
        array $sheetRows,
        array $indexes,
        string $cleanShift,
        ?int $lineId
    ): array {
        $reserved = [];
        foreach ($sheetRows as $item) {
            $rawJm = $this->safeVal($item['job_master'] ?? null, '');
            $rawJn = $this->safeVal($item['job_no'] ?? null, '');
            $rawKet = strtoupper((string) $this->safeVal($item['keterangan'] ?? null, ''));
            if (str_contains($rawKet, 'DELETE') || str_contains($rawKet, 'HAPUS')) {
                continue;
            }
            $jm = strtoupper(trim((string) $rawJm));
            $jn = strtoupper(trim((string) $rawJn));
            $rowNo = (int) $this->safeVal($item['row_no'] ?? null, 0);
            if ($jm === '' && $jn === '') {
                continue;
            }
            $key = $cleanShift.'||'.$lineId.'||'.$jm.'||'.$jn.'||'.$rowNo;
            $exact = $indexes['byKey'][$key][0] ?? null;
            if ($exact instanceof ProductionPlan && ! $exact->isChildSlice()) {
                $reserved[(int) $exact->id] = $rowNo;
            }
        }

        return $reserved;
    }

    /**
     * Compute [0-based rank, total count] of independent root schedules in $sheetRows
     * that share ($cleanJobMaster, $cleanJobNo).
     *
     * @return array{0:int,1:int}
     */
    private function incomingRootRank(
        array $sheetRows,
        int $targetRowIdx,
        string $cleanJobMaster,
        string $cleanJobNo
    ): array {
        $rank = 0;
        $total = 0;
        $sawBreak = false;
        $lastJn = '';
        $lastJm = '';
        $lastPlan = 0.0;

        foreach ($sheetRows as $idx => $item) {
            $rawJm = strtoupper(trim((string) $this->safeVal($item['job_master'] ?? null, '')));
            $rawJn = strtoupper(trim((string) $this->safeVal($item['job_no'] ?? null, '')));
            $rawKet = strtoupper((string) $this->safeVal($item['keterangan'] ?? null, ''));
            $rowType = strtolower((string) ($item['row_type'] ?? 'job'));

            if (str_contains($rawKet, 'DELETE') || str_contains($rawKet, 'HAPUS')) {
                continue;
            }

            $isBreak = $rowType === 'break';
            if (! $isBreak) {
                foreach (['BREAK', 'ISTIRAHAT', 'CINGKORAK', 'BREAKTIME', 'BREAK TIME', 'ISHOMA'] as $kw) {
                    if (str_contains($rawJn, $kw) || str_contains($rawJm, $kw) || str_contains($rawKet, $kw)) {
                        $isBreak = true;
                        break;
                    }
                }
            }
            if ($isBreak) {
                $sawBreak = true;
                continue;
            }

            $planQty = (float) $this->safeVal($item['plan'] ?? null, 0);
            $dct = (float) $this->safeVal($item['dct'] ?? null, 0);
            $reg = (float) $this->safeVal($item['reg_active'] ?? null, 0);
            $cleanStart = $this->cleanTime($this->safeVal($item['start_time'] ?? null, ''));
            $cleanFinish = $this->cleanTime($this->safeVal($item['finish_time'] ?? null, ''));
            if ($planQty <= 0 && ! (($dct > 0 || $reg > 0) && $cleanStart !== '' && $cleanFinish !== '')) {
                continue;
            }

            $isContinuation = false;
            if ($sawBreak && $lastJn !== '' && $rawJn === $lastJn && ($rawJm === '' || $lastJm === '' || $rawJm === $lastJm)) {
                if (($dct <= 0 && $reg <= 0) || ($lastPlan <= 0 && $planQty > 0)) {
                    $isContinuation = true;
                }
            }

            if (! $isContinuation) {
                $matchesTarget = ($cleanJobNo !== '' ? $rawJn === $cleanJobNo : true)
                    && ($cleanJobMaster !== '' ? $rawJm === $cleanJobMaster : true);
                if ($matchesTarget) {
                    if ($idx < $targetRowIdx) {
                        $rank++;
                    }
                    $total++;
                }
                $lastJn = $rawJn;
                $lastJm = $rawJm;
                $lastPlan = $planQty;
            }
            $sawBreak = false;
        }

        return [$rank, max(1, $total)];
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
        array $usedPlanIds,
        ?ProductionPlan $continuationRoot = null,
        array $reservedPlanIds = [],
        int $incomingIndependentRank = 0,
        int $incomingIndependentTotal = 1
    ): ?ProductionPlan {
        if ($rowType === 'break') {
            $break = $indexes['byKey'][$matchKeyWithRow][0] ?? null;
            if ($break && ! isset($usedPlanIds[(int) $break->id])) {
                return $break;
            }

            $breakLabelKey = $shiftName.'||'.$lineId.'||break||'.$cleanJobNo;
            $break = $this->popCandidatePlan($indexes['byBreakLabelQueue'], $breakLabelKey, $usedPlanIds);
            if ($break) {
                return $break;
            }

            return $this->firstUnique($indexes['byBreakLabel'], $breakLabelKey);
        }

        // 1. If this row is a post-break slice continuation of an already-matched root schedule,
        //    match against the existing child slices of that exact root schedule first!
        if ($continuationRoot !== null && $continuationRoot->id) {
            $rootId = (int) $continuationRoot->id;
            $sliceMatch = $this->popCandidatePlan($indexes['bySlicesOfRoot'], (string) $rootId, $usedPlanIds);
            if ($sliceMatch) {
                return $sliceMatch;
            }
        }

        // 2. Exact identity + row_no match (only accept a child slice if we are in a continuation of its root)
        $primaryCandidates = $indexes['byKey'][$matchKeyWithRow] ?? [];
        foreach ($primaryCandidates as $primary) {
            if (isset($usedPlanIds[(int) $primary->id])) {
                continue;
            }
            if ($continuationRoot === null && $primary->isChildSlice()) {
                continue;
            }
            if ($continuationRoot !== null && (int) $primary->resolveRootPlan()->id !== (int) $continuationRoot->id) {
                continue;
            }

            return $primary;
        }

        // 3. When an existing root schedule was originally a single unsplit row, and the revision
        //    splits it across a break, the 2nd slice has no existing child slice in DB and must be created as new.
        if ($continuationRoot !== null) {
            return null;
        }

        // Exclude any plan ID that is already reserved by an exact (identity + row_no) match on another row in this sheet
        $effectiveExclusions = $usedPlanIds + $reservedPlanIds;

        // 4. Match by full identity (shift + line + job_master + job_no + start_time) on root schedules
        if ($cleanJobMaster !== '' && $cleanJobNo !== '' && ! in_array($cleanJobNo, ['-', 'BREAK'], true)) {
            $identityKey = $shiftName.'||'.$lineId.'||'.$cleanJobMaster.'||'.$cleanJobNo;
            if ($cleanStart !== '') {
                $identityStartKey = $identityKey.'||'.$cleanStart;
                $match = $this->uniqueCandidatePlan($indexes['byRootIdentityStart'], $identityStartKey, $effectiveExclusions);
                if ($match) {
                    return $match;
                }
            }

            // Strict uniqueness across ALL existing roots of this identity (not just remaining unused ones):
            // if there was only 1 existing root and 1 incoming root, it is unambiguously the same schedule shifted in row_no.
            $allRootsForIdentity = $indexes['byRootIdentityQueue'][$identityKey] ?? [];
            if (count($allRootsForIdentity) === 1 && $incomingIndependentTotal === 1) {
                $onlyRoot = $allRootsForIdentity[0];
                if (! isset($effectiveExclusions[(int) $onlyRoot->id])) {
                    return $onlyRoot;
                }
            }

            // When multiple independent root schedules share ($cleanJobMaster, $cleanJobNo),
            // match the k-th incoming independent root to the k-th existing root ONLY if that k-th existing root
            // is not already claimed/reserved by an exact row_no match. Never cross-bind a newly added 3rd schedule
            // to an omitted 1st schedule!
            if (count($allRootsForIdentity) > 1 && isset($allRootsForIdentity[$incomingIndependentRank])) {
                $positionalRoot = $allRootsForIdentity[$incomingIndependentRank];
                if (! isset($effectiveExclusions[(int) $positionalRoot->id])) {
                    return $positionalRoot;
                }
            }
        }

        // 5. Match by job_no on root schedules (handles cases where job_master formatting changed or was blank)
        if ($cleanJobNo !== '' && ! in_array($cleanJobNo, ['-', 'BREAK'], true)) {
            $noKey = $shiftName.'||'.$lineId.'||'.$cleanJobNo;
            if ($cleanStart !== '') {
                $noStartKey = $noKey.'||'.$cleanStart;
                $match = $this->uniqueCandidatePlan($indexes['byRootJobNoStart'], $noStartKey, $effectiveExclusions)
                    ?? $this->firstUnique($indexes['byJobNoStart'], $noStartKey);
                if ($match && ! isset($effectiveExclusions[(int) $match->id]) && ! $match->isChildSlice()) {
                    return $match;
                }
            }

            $allRootsForJobNo = $indexes['byRootJobNoQueue'][$noKey] ?? [];
            if (count($allRootsForJobNo) === 1 && $incomingIndependentTotal === 1) {
                $onlyRoot = $allRootsForJobNo[0];
                if (! isset($effectiveExclusions[(int) $onlyRoot->id])) {
                    return $onlyRoot;
                }
            }

            if (count($allRootsForJobNo) > 1 && isset($allRootsForJobNo[$incomingIndependentRank])) {
                $positionalRoot = $allRootsForJobNo[$incomingIndependentRank];
                if (! isset($effectiveExclusions[(int) $positionalRoot->id])) {
                    return $positionalRoot;
                }
            }
        }

        // 6. Match by job_master (part number) on root schedules (handles job_no rename on same part)
        if ($cleanJobMaster !== '' && $cleanJobMaster !== 'BREAK') {
            $jobMasterKey = $shiftName.'||'.$lineId.'||'.$cleanJobMaster;
            $looseKey = $jobMasterKey.'||'.(int) $finalRowNo;
            $match = $this->firstUnique($indexes['byLooseKey'], $looseKey);
            if ($match && ! isset($effectiveExclusions[(int) $match->id]) && ! $match->isChildSlice()) {
                return $match;
            }

            $allRootsForJobMaster = $indexes['byRootJobMasterQueue'][$jobMasterKey] ?? [];
            if (count($allRootsForJobMaster) === 1 && $incomingIndependentTotal === 1) {
                $onlyRoot = $allRootsForJobMaster[0];
                if (! isset($effectiveExclusions[(int) $onlyRoot->id])) {
                    return $onlyRoot;
                }
            }
        }

        return null;
    }

    private function uniqueCandidatePlan(array $queueMap, string $key, array $usedPlanIds): ?ProductionPlan
    {
        $candidates = array_values(array_filter(
            $queueMap[$key] ?? [],
            fn ($candidate) => $candidate instanceof ProductionPlan && ! isset($usedPlanIds[(int) $candidate->id])
        ));

        if (count($candidates) !== 1) {
            return null;
        }

        return $candidates[0];
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
        $byRootIdentityQueue = [];
        $byRootIdentityStart = [];
        $byIdentityQueue = [];
        $byRootJobNoQueue = [];
        $byRootJobNoStart = [];
        $byRootJobMasterQueue = [];
        $bySlicesOfRoot = [];

        $sortedPlans = $existingPlans->sortBy(
            fn (ProductionPlan $p) => sprintf('%08d-%08d', (int) ($p->row_no ?? 0), (int) $p->id)
        )->values();

        // Pre-compute implicit break-split groups for legacy unlinked rows in DB so that
        // existing multi-row break splits are recognized as root + child slices even if parent_job_id was null.
        $implicitParentByPlanId = [];
        $plansBySection = $sortedPlans->groupBy(fn (ProductionPlan $p) => $this->sectionKey($p));
        foreach ($plansBySection as $sectionPlans) {
            $lastJob = null;
            $currentRoot = null;
            $sawBreak = false;
            foreach ($sectionPlans as $plan) {
                if ($plan->row_type === 'break') {
                    if ($lastJob !== null) {
                        $sawBreak = true;
                    }
                    continue;
                }
                if ($plan->row_type !== 'job') {
                    continue;
                }
                if ($plan->isChildSlice() && $plan->parent_job_id) {
                    $implicitParentByPlanId[(int) $plan->id] = (int) $plan->resolveRootPlan($existingPlans)->id;
                    $lastJob = $plan;
                    $sawBreak = false;
                    continue;
                }

                $jn = strtoupper(trim((string) $plan->job_no));
                $jm = strtoupper(trim((string) $plan->job_master));
                $isImplicitSlice = false;
                if ($sawBreak && $lastJob !== null && $currentRoot !== null) {
                    $prevJn = strtoupper(trim((string) $lastJob->job_no));
                    $prevJm = strtoupper(trim((string) $lastJob->job_master));
                    if ($jn !== '' && $jn === $prevJn && ($jm === '' || $prevJm === '' || $jm === $prevJm)) {
                        $postDct = (float) ($plan->dct ?? 0);
                        $postReg = (float) ($plan->reg_active ?? 0);
                        $prePlan = (float) ($lastJob->plan ?? 0);
                        $postPlan = (float) ($plan->plan ?? 0);
                        if (($postDct <= 0 && $postReg <= 0) || ($prePlan <= 0 && $postPlan > 0)) {
                            $isImplicitSlice = true;
                        }
                    }
                }

                if ($isImplicitSlice && $currentRoot !== null) {
                    $implicitParentByPlanId[(int) $plan->id] = (int) $currentRoot->id;
                } else {
                    $currentRoot = $plan;
                }
                $lastJob = $plan;
                $sawBreak = false;
            }
        }

        foreach ($sortedPlans as $plan) {
            $cleanShift = $this->cleanShift($plan->shift_name);
            $lineId = $plan->line_master_id;
            $jobMaster = strtoupper(trim((string) $plan->job_master));
            $jobNo = strtoupper(trim((string) $plan->job_no));
            $startTime = trim((string) $plan->start_time);

            if ($plan->row_type === 'break') {
                $breakLabel = $jobNo !== '' ? $jobNo : $jobMaster;
                $breakKey = $cleanShift.'||'.$lineId.'||break||'.$breakLabel
                    .'||'.$startTime.'||'.trim((string) $plan->finish_time);
                $byKey[$breakKey][] = $plan;
                if ($breakLabel !== '') {
                    $byBreakLabel[$cleanShift.'||'.$lineId.'||break||'.$breakLabel][] = $plan;
                    $byBreakLabelQueue[$cleanShift.'||'.$lineId.'||break||'.$breakLabel][] = $plan;
                }

                continue;
            }

            $parentRootId = $implicitParentByPlanId[(int) $plan->id] ?? null;
            $isSlice = $parentRootId !== null && $parentRootId !== (int) $plan->id;

            if ($isSlice) {
                $bySlicesOfRoot[(string) $parentRootId][] = $plan;
            }

            $byKey[$cleanShift.'||'.$lineId.'||'.$jobMaster.'||'.$jobNo.'||'.(int) $plan->row_no][] = $plan;
            $byKey[$cleanShift.'||'.$lineId.'||'.$jobMaster.'||'.$jobNo][] = $plan;

            if ($jobMaster !== '' && $jobNo !== '' && ! in_array($jobNo, ['-', 'TOTAL FINISH', 'TOTAL FNISH', 'FINISH'], true)) {
                $identityKey = $cleanShift.'||'.$lineId.'||'.$jobMaster.'||'.$jobNo;
                $byIdentityQueue[$identityKey][] = $plan;
                if (! $isSlice) {
                    $byRootIdentityQueue[$identityKey][] = $plan;
                    if ($startTime !== '') {
                        $byRootIdentityStart[$identityKey.'||'.$startTime][] = $plan;
                    }
                }
            }

            if ($jobNo !== '' && ! in_array($jobNo, ['-', 'TOTAL FINISH', 'TOTAL FNISH', 'FINISH'], true)) {
                $byJobNo[$cleanShift.'||'.$lineId.'||'.$jobNo][] = $plan;
                $byJobNoQueue[$cleanShift.'||'.$lineId.'||'.$jobNo][] = $plan;
                $byJobNoStart[$cleanShift.'||'.$lineId.'||'.$jobNo.'||'.$startTime][] = $plan;
                if (! $isSlice) {
                    $byRootJobNoQueue[$cleanShift.'||'.$lineId.'||'.$jobNo][] = $plan;
                    if ($startTime !== '') {
                        $byRootJobNoStart[$cleanShift.'||'.$lineId.'||'.$jobNo.'||'.$startTime][] = $plan;
                    }
                }
            }

            if ($jobMaster !== '') {
                $byJobMaster[$cleanShift.'||'.$lineId.'||'.$jobMaster][] = $plan;
                $byJobMasterQueue[$cleanShift.'||'.$lineId.'||'.$jobMaster][] = $plan;
                $byLooseKey[$cleanShift.'||'.$lineId.'||'.$jobMaster.'||'.(int) $plan->row_no][] = $plan;
                if (! $isSlice) {
                    $byRootJobMasterQueue[$cleanShift.'||'.$lineId.'||'.$jobMaster][] = $plan;
                }
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
            'byBreakLabelQueue',
            'byRootIdentityQueue',
            'byRootIdentityStart',
            'byIdentityQueue',
            'byRootJobNoQueue',
            'byRootJobNoStart',
            'byRootJobMasterQueue',
            'bySlicesOfRoot'
        );
    }

    /**
     * Identify and link break-split schedule slices within an imported section so that
     * ONE SCHEDULE = ONE JOB IDENTITY holds directly after Excel import.
     *
     * Rules:
     * - A job row immediately following one or more 'break' rows is linked to the job row
     *   immediately preceding the break(s) ONLY IF:
     *   1) Both rows have the exact same non-empty job_no and job_master.
     *   2) Either:
     *      (a) the post-break row has no new changeover (dct == 0 and reg_active == 0), OR
     *      (b) the pre-break slice was Dandori-only (pre-break plan == 0 and post-break plan > 0), OR
     *      (c) the pre-break finish_time matches the break start_time AND the post-break start_time
     *          matches the break finish_time AND post-break dct == 0.
     * - Independent schedules with the same job_no / part_number (e.g. separated by another job
     *   or having their own changeover/dandori after a completed batch) remain separate root schedules!
     *
     * @param  array<int, ProductionPlan>  $sectionPlans
     * @param  array<int, JobMaster>  $jobsByPlanId
     */
    private function linkBreakSplitContinuations(
        array $sectionPlans,
        array &$jobsByPlanId,
        string $parsedDate,
        bool $allowActiveMetadataUpdate = false
    ): void {
        if (count($sectionPlans) < 2) {
            return;
        }

        // Sort by row_no ascending, then id ascending to follow exact sheet sequence
        usort($sectionPlans, function (ProductionPlan $a, ProductionPlan $b) {
            $rA = (int) ($a->row_no ?? 0);
            $rB = (int) ($b->row_no ?? 0);
            return $rA !== $rB ? ($rA <=> $rB) : ((int) $a->id <=> (int) $b->id);
        });

        /** @var ProductionPlan|null $lastJobPlan */
        $lastJobPlan = null;
        /** @var ProductionPlan|null $currentRootPlan */
        $currentRootPlan = null;
        $currentSessionNo = 1;
        $sawBreakSinceLastJob = false;

        /** @var array<int, array<int, ProductionPlan>> $groupsByRootId */
        $groupsByRootId = [];

        foreach ($sectionPlans as $plan) {
            $type = $plan->row_type ?? 'job';
            if ($type === 'break') {
                if ($lastJobPlan !== null) {
                    $sawBreakSinceLastJob = true;
                }
                continue;
            }

            if ($type !== 'job') {
                continue;
            }

            $jn = strtoupper(trim((string) ($plan->job_no ?? '')));
            $jm = strtoupper(trim((string) ($plan->job_master ?? '')));

            if ($jn === '' && $jm === '') {
                $lastJobPlan = null;
                $currentRootPlan = null;
                $sawBreakSinceLastJob = false;
                continue;
            }

            $isBreakContinuation = false;
            if ($sawBreakSinceLastJob && $lastJobPlan !== null && $currentRootPlan !== null) {
                $prevJn = strtoupper(trim((string) ($lastJobPlan->job_no ?? '')));
                $prevJm = strtoupper(trim((string) ($lastJobPlan->job_master ?? '')));

                $sameIdentity = ($jn !== '' && $jn === $prevJn)
                    && ($jm === '' || $prevJm === '' || $jm === $prevJm);

                if ($sameIdentity) {
                    $postDct = (float) ($plan->dct ?? 0);
                    $postReg = (float) ($plan->reg_active ?? 0);
                    $prePlanQty = (float) ($lastJobPlan->plan ?? 0);
                    $postPlanQty = (float) ($plan->plan ?? 0);

                    // Continuing across a break: either post-break has 0 DCT/RegActive,
                    // or pre-break was a Dandori-only slice (plan == 0) before the break.
                    if (($postDct <= 0 && $postReg <= 0) || ($prePlanQty <= 0 && $postPlanQty > 0)) {
                        $isBreakContinuation = true;
                    }
                }
            }

            if ($isBreakContinuation && $currentRootPlan !== null) {
                $currentSessionNo++;
                $splitGroup = $currentRootPlan->split_group ?: ('SPLIT-' . $currentRootPlan->id);

                if ($currentRootPlan->session_no === null) {
                    $currentRootPlan->session_no = 1;
                }
                if ($currentRootPlan->split_group !== $splitGroup) {
                    $currentRootPlan->split_group = $splitGroup;
                }

                $plan->parent_job_id = $currentRootPlan->id;
                $plan->session_no = $currentSessionNo;
                $plan->split_group = $splitGroup;
                $plan->save();

                $groupsByRootId[(int) $currentRootPlan->id][] = $plan;
            } else {
                // Ensure an independent root schedule does not keep a stale parent_job_id
                if ($plan->parent_job_id !== null) {
                    $plan->parent_job_id = null;
                    $plan->session_no = null;
                    $plan->split_group = null;
                    $plan->save();
                }
                $currentRootPlan = $plan;
                $currentSessionNo = 1;
                $groupsByRootId[(int) $currentRootPlan->id] = [$currentRootPlan];
            }

            $lastJobPlan = $plan;
            $sawBreakSinceLastJob = false;
        }

        // Update original_plan on root schedules that have break-split continuations
        $candidatePool = collect($sectionPlans);
        foreach ($groupsByRootId as $rootId => $slices) {
            /** @var ProductionPlan $root */
            $root = $slices[0];
            $rootLockState = $this->scheduleLockState($root);
            $rootLocked = $rootLockState !== null
                && ! ($rootLockState === 'running' && $allowActiveMetadataUpdate);

            if (count($slices) > 1) {
                $totalPlanQty = collect($slices)->sum(fn (ProductionPlan $s) => (float) ($s->plan ?? 0));
                if (! $rootLocked || empty($root->original_plan)) {
                    $root->original_plan = $totalPlanQty;
                }
                $root->session_no = 1;
                $root->split_group = $root->split_group ?: ('SPLIT-' . $root->id);
                $root->save();

                foreach ($slices as $slice) {
                    if ((int) $slice->id !== (int) $root->id && empty($slice->original_plan)) {
                        $slice->original_plan = $slice->plan;
                        $slice->save();
                    }
                }
            } elseif (! $rootLocked && $root->original_plan !== null && (float) $root->original_plan !== (float) $root->plan) {
                $root->original_plan = $root->plan;
                $root->save();
            }

            // Re-sync root JobMaster with the canonical effective target quantity
            $this->syncJobMasterSchedule(
                $root->fresh(),
                $jobsByPlanId,
                $parsedDate,
                $candidatePool,
                $allowActiveMetadataUpdate
            );
        }
    }

    private function syncJobMasterSchedule(
        ProductionPlan $plan,
        array &$jobsByPlanId,
        string $parsedDate,
        ?Collection $candidatePool = null,
        bool $allowActiveMetadataUpdate = false
    ): void {
        if ($plan->row_type !== 'job') {
            return;
        }

        $planId = (int) $plan->id;
        $jobMaster = $jobsByPlanId[$planId] ?? null;

        if (! $jobMaster) {
            return;
        }

        $identifier = $this->identifierFor($plan);
        $effectiveTarget = $plan->isChildSlice()
            ? (int) ($plan->plan ?? 0)
            : $plan->effectiveTargetQty($candidatePool);
        $effectiveFinish = $plan->isChildSlice()
            ? $plan->finish_time
            : $plan->effectiveFinishTime($candidatePool);

        $lockState = $this->scheduleLockState($plan);
        $locked = $lockState !== null
            && ! ($lockState === 'running' && $allowActiveMetadataUpdate);

        $updates = [
            'sequence_no' => $plan->row_no ?: ($jobMaster->sequence_no ?? 1),
            'capacity' => (int) ($plan->qty_plt ?? 0),
            'ppc_revision_id' => $plan->ppc_revision_id,
            'ppc_revision_active' => true,
        ];

        if (! $locked) {
            $updates['target_qty'] = $effectiveTarget;

            if ($plan->start_time) {
                $updates['plan_start'] = Carbon::parse($parsedDate.' '.$plan->start_time)->startOfMinute();
            }
            if ($effectiveFinish) {
                $updates['plan_end'] = Carbon::parse($parsedDate.' '.$effectiveFinish)->startOfMinute();
            }
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

    private function storeRevision(
        string $parsedDate,
        array $sheets,
        array $before,
        array $after,
        ?int $userId,
        array $options = []
    ): ?ScheduleRevision {
        try {
            $shiftNames = $this->revisionShiftNames($sheets);

            return ScheduleRevision::create([
                'plan_date' => $parsedDate,
                'shift_name' => $shiftNames,
                'action' => 'import',
                'section_scope' => $options['section_scope'] ?? null,
                'source_filename' => $options['source_filename'] ?? null,
                'snapshot_before' => [
                    'mode' => 'merge',
                    'status' => $options['status'] ?? 'completed',
                    'backup_available' => (bool) ($options['backup_available'] ?? false),
                    'plans' => $before,
                ],
                'snapshot_after' => $after ?: null,
                'created_by' => $userId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Schedule revision (merge import) failed: '.$e->getMessage());

            return null;
        }
    }

    private function finalizeRevision(
        ?ScheduleRevision $revision,
        array $after,
        string $parsedDate,
        array $sheets,
        array $before,
        ?int $userId,
        array $options = []
    ): void {
        if (! $revision) {
            return;
        }

        try {
            $revision->update([
                'shift_name' => $this->revisionShiftNames($sheets),
                'section_scope' => $options['section_scope'] ?? null,
                'source_filename' => $options['source_filename'] ?? null,
                'snapshot_before' => [
                    'mode' => 'merge',
                    'status' => 'completed',
                    'backup_available' => (bool) ($options['backup_available'] ?? false),
                    'plans' => $before,
                ],
                'snapshot_after' => $after,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Schedule revision finalization failed: '.$e->getMessage());
        }
    }

    private function revisionShiftNames(array $sheets): string
    {
        $shiftNames = collect($sheets)
            ->map(fn ($sheet) => $this->displayShift($sheet['shift_name'] ?? ''))
            ->filter()
            ->unique()
            ->implode(', ');

        return $shiftNames !== '' ? $shiftNames : 'All';
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
        return trim($shiftName).'||'.trim((string) $pressName);
    }

    public function cleanShift(?string $shiftName): string
    {
        $clean = strtoupper(trim((string) $shiftName));
        $clean = preg_replace('/\s*\(.*?REV.*?\)|\s*REV(?:ISI)?\b/i', '', $clean);
        $clean = preg_replace('/\bSHIFT\s*/i', '', $clean);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));

        if (str_contains($clean, 'MALAM') || str_contains($clean, 'NIGHT') || preg_match('/\b2\b/', $clean)) {
            return 'Malam';
        }

        return 'Pagi';
    }

    public function displayShift(?string $shiftName): string
    {
        return 'Shift '.$this->cleanShift($shiftName);
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
            'deleted' => 0,
            'protected_from_deletion' => 0,
            'blocked_changes' => 0,
            'not_in_revision' => 0,
            'rows_imported' => 0,
        ];

        return [
            'counts' => $counts,
            'added' => [],
            'updated' => [],
            'unchanged' => [],
            'deleted' => [],
            'protected_from_deletion' => [],
            'blocked_changes' => [],
            'not_in_revision' => [],
            'revision_id' => null,
            'preview' => [
                'counts' => $counts,
                'added' => [],
                'updated' => [],
                'unchanged' => [],
                'deleted' => [],
                'protected_from_deletion' => [],
                'blocked_changes' => [],
                'not_in_revision' => [],
                'revision_id' => null,
            ],
        ];
    }
}
