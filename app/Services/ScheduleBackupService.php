<?php

namespace App\Services;

use App\Models\DailyProduction;
use App\Models\Dandori;
use App\Models\DandoriSession;
use App\Models\Downtime;
use App\Models\JobFinishReason;
use App\Models\JobMaster;
use App\Models\ProductionLog;
use App\Models\ProductionPlan;
use App\Models\ProductionSession;
use App\Models\RecoveryItem;
use App\Models\RepairRejectLog;
use App\Models\ScheduleBackup;
use App\Models\ScheduleRevision;
use App\Models\ShiftSubmission;
use App\Models\Signature;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ScheduleBackupService
{
    /**
     * Fields on ProductionPlan that represent live production actuals/status and must NEVER
     * be downgraded or erased on plans that have post-snapshot or ongoing production activity.
     */
    public const PROTECTED_PLAN_FIELDS = [
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
     * Capture a full snapshot of PPC schedule and linked operational context for a date.
     */
    public function captureSnapshot(
        string $planDate,
        string $backupType = 'pre_import',
        ?int $userId = null,
        array $options = []
    ): ?ScheduleBackup {
        if (! Schema::hasTable('schedule_backups')) {
            return null;
        }

        $parsedDate = Carbon::parse($planDate)->toDateString();
        $shiftScope = (string) ($options['shift_scope'] ?? 'all');
        $pressScope = (string) ($options['press_scope'] ?? 'all');

        $plansQuery = ProductionPlan::whereDate('plan_date', $parsedDate)
            ->orderBy('press_name')
            ->orderBy('shift_name')
            ->orderBy('row_no')
            ->orderBy('id');

        /** @var Collection<int, ProductionPlan> $plans */
        $plans = $plansQuery->get();

        // If this is an automatic pre-import backup and there are 0 existing plans for the date,
        // we skip creating an empty pre-import snapshot; instead, after import we can capture an 'initial' snapshot.
        if ($plans->isEmpty() && empty($options['allow_empty'])) {
            return null;
        }

        // If no backup exists yet for this date and we already have plans, mark first automatic capture as 'initial' if requested
        $hasExistingBackup = ScheduleBackup::whereDate('plan_date', $parsedDate)->exists();
        if (! $hasExistingBackup && $backupType === 'pre_import' && $plans->isNotEmpty()) {
            $backupType = 'initial';
        }

        $planIds = $plans->pluck('id')->map(fn ($id) => (int) $id)->all();
        $connectedJobs = $this->findConnectedJobMasters($planIds);
        $jobIds = $connectedJobs->pluck('id')->map(fn ($id) => (int) $id)->all();

        $dailyProductions = ! empty($jobIds) && Schema::hasTable('daily_productions')
            ? DailyProduction::whereIn('job_master_id', $jobIds)->get()
            : collect();

        $productionSessions = ! empty($jobIds) && Schema::hasTable('production_sessions')
            ? ProductionSession::whereIn('job_master_id', $jobIds)->get()
            : collect();

        $productionLogs = ! empty($jobIds) && Schema::hasTable('production_logs')
            ? ProductionLog::whereIn('job_master_id', $jobIds)->get()
            : collect();

        $downtimes = ! empty($jobIds) && Schema::hasTable('downtimes')
            ? Downtime::whereIn('job_master_id', $jobIds)->get()
            : collect();

        $dandoris = ! empty($jobIds) && Schema::hasTable('dandoris')
            ? Dandori::whereIn('next_job_id', $jobIds)->orWhereIn('previous_job_id', $jobIds)->get()
            : collect();

        $dandoriSessions = ! empty($jobIds) && Schema::hasTable('dandori_sessions')
            ? DandoriSession::whereIn('job_master_id', $jobIds)->get()
            : collect();

        $repairRejectLogs = ! empty($jobIds) && Schema::hasTable('repair_reject_logs')
            ? RepairRejectLog::whereIn('job_master_id', $jobIds)->get()
            : collect();

        $finishReasons = ! empty($jobIds) && Schema::hasTable('job_finish_reasons')
            ? JobFinishReason::whereIn('job_master_id', $jobIds)->get()
            : collect();

        $recoveryItems = (! empty($planIds) || ! empty($jobIds)) && Schema::hasTable('recovery_items')
            ? RecoveryItem::where(function ($q) use ($planIds, $jobIds, $parsedDate) {
                $recoveryCols = array_flip(Schema::getColumnListing('recovery_items'));
                if (! empty($planIds) && isset($recoveryCols['production_plan_id'])) {
                    $q->orWhereIn('production_plan_id', $planIds);
                }
                if (! empty($jobIds) && isset($recoveryCols['job_master_id'])) {
                    $q->orWhereIn('job_master_id', $jobIds);
                }
                if (isset($recoveryCols['source_date'])) {
                    $q->orWhereDate('source_date', $parsedDate);
                }
                if (isset($recoveryCols['original_date'])) {
                    $q->orWhereDate('original_date', $parsedDate);
                }
            })->get()
            : collect();

        $signatures = Schema::hasTable('signatures')
            ? Signature::whereDate('work_date', $parsedDate)->get()
            : collect();

        $shiftSubmissions = Schema::hasTable('shift_submissions')
            ? ShiftSubmission::whereDate('work_date', $parsedDate)->get()
            : collect();

        $mergeService = app(PpcScheduleMergeService::class);
        $activeJobsCount = $plans->filter(fn (ProductionPlan $p) => $mergeService->planHasProductionActivity($p))->count();

        $plansArray = $plans->map(fn (ProductionPlan $plan) => $this->serializeModel($plan))->values()->all();
        $jobsArray = $connectedJobs->map(fn (JobMaster $job) => $this->serializeModel($job))->values()->all();

        $checksumSource = json_encode([
            'plan_date' => $parsedDate,
            'plans' => collect($plansArray)->map(fn ($p) => [
                'id' => $p['id'] ?? null,
                'shift_name' => $p['shift_name'] ?? null,
                'press_name' => $p['press_name'] ?? null,
                'row_no' => $p['row_no'] ?? null,
                'row_type' => $p['row_type'] ?? null,
                'job_master' => $p['job_master'] ?? null,
                'job_no' => $p['job_no'] ?? null,
                'plan' => $p['plan'] ?? 0,
                'start_time' => $p['start_time'] ?? null,
                'finish_time' => $p['finish_time'] ?? null,
            ])->all(),
        ]);
        $checksum = hash('sha256', (string) $checksumSource);

        $byPress = $plans->where('row_type', 'job')
            ->groupBy('press_name')
            ->map(fn (Collection $group) => [
                'jobs' => $group->count(),
                'plan_pcs' => (float) $group->sum('plan'),
                'ok_pcs' => (float) $group->sum('ok'),
            ])->all();

        $byShift = $plans->where('row_type', 'job')
            ->groupBy('shift_name')
            ->map(fn (Collection $group) => [
                'jobs' => $group->count(),
                'plan_pcs' => (float) $group->sum('plan'),
                'ok_pcs' => (float) $group->sum('ok'),
            ])->all();

        $summaryMeta = [
            'by_press' => $byPress,
            'by_shift' => $byShift,
            'total_plan_pcs' => (float) $plans->where('row_type', 'job')->sum('plan'),
            'total_ok_pcs' => (float) $plans->where('row_type', 'job')->sum('ok'),
            'breaks_count' => $plans->where('row_type', 'break')->count(),
            'shift_submissions_count' => $shiftSubmissions->count(),
            'notes' => $options['notes'] ?? null,
        ];

        $payload = [
            'version' => 1,
            'captured_at' => now()->toIso8601String(),
            'plan_date' => $parsedDate,
            'production_plans' => $plansArray,
            'job_masters' => $jobsArray,
            'daily_productions' => $dailyProductions->map(fn ($m) => $this->serializeModel($m))->values()->all(),
            'production_sessions' => $productionSessions->map(fn ($m) => $this->serializeModel($m))->values()->all(),
            'production_logs' => $productionLogs->map(fn ($m) => $this->serializeModel($m))->values()->all(),
            'downtimes' => $downtimes->map(fn ($m) => $this->serializeModel($m))->values()->all(),
            'dandoris' => $dandoris->map(fn ($m) => $this->serializeModel($m))->values()->all(),
            'dandori_sessions' => $dandoriSessions->map(fn ($m) => $this->serializeModel($m))->values()->all(),
            'repair_reject_logs' => $repairRejectLogs->map(fn ($m) => $this->serializeModel($m))->values()->all(),
            'job_finish_reasons' => $finishReasons->map(fn ($m) => $this->serializeModel($m))->values()->all(),
            'recovery_items' => $recoveryItems->map(fn ($m) => $this->serializeModel($m))->values()->all(),
            'signatures' => $signatures->map(fn ($m) => $this->serializeModel($m))->values()->all(),
            'shift_submissions' => $shiftSubmissions->map(fn ($m) => $this->serializeModel($m))->values()->all(),
        ];

        $label = $options['label'] ?? $this->defaultLabel($backupType, $parsedDate, $options['source_filename'] ?? null);

        return ScheduleBackup::create([
            'plan_date' => $parsedDate,
            'shift_scope' => $shiftScope,
            'press_scope' => $pressScope,
            'backup_type' => $backupType,
            'label' => $label,
            'source_filename' => $options['source_filename'] ?? null,
            'checksum' => $checksum,
            'plans_count' => count($plansArray),
            'jobs_count' => count($jobsArray),
            'active_jobs_count' => $activeJobsCount,
            'signatures_count' => $signatures->count(),
            'summary_meta' => $summaryMeta,
            'snapshot_payload' => $payload,
            'created_by' => $userId,
            'restored_from_id' => $options['restored_from_id'] ?? null,
        ]);
    }

    /**
     * Ensure an initial backup exists for a date after the very first schedule import.
     */
    public function ensureInitialBackup(string $planDate, ?int $userId = null, ?string $sourceFilename = null): ?ScheduleBackup
    {
        if (! Schema::hasTable('schedule_backups')) {
            return null;
        }

        $parsedDate = Carbon::parse($planDate)->toDateString();
        if (ScheduleBackup::whereDate('plan_date', $parsedDate)->exists()) {
            return null;
        }

        return $this->captureSnapshot($parsedDate, 'initial', $userId, [
            'source_filename' => $sourceFilename,
            'label' => 'Initial Schedule Snapshot ('.$parsedDate.')',
        ]);
    }

    /**
     * Build a non-destructive preview/comparison between a backup snapshot and the current live schedule for that date.
     */
    public function compareWithLive(ScheduleBackup $backup): array
    {
        $parsedDate = $backup->plan_date->toDateString();
        $payload = $backup->snapshot_payload ?? [];
        $snapshotPlans = collect($payload['production_plans'] ?? []);

        /** @var Collection<int, ProductionPlan> $livePlans */
        $livePlans = ProductionPlan::whereDate('plan_date', $parsedDate)
            ->orderBy('press_name')
            ->orderBy('shift_name')
            ->orderBy('row_no')
            ->orderBy('id')
            ->get();

        $mergeService = app(PpcScheduleMergeService::class);
        $liveById = $livePlans->keyBy('id');
        $snapshotById = $snapshotPlans->keyBy('id');

        $willRestoreMissing = [];
        $willUpdateSchedule = [];
        $unchanged = [];
        $postSnapshotPlans = [];
        $protectedActivePlans = [];

        foreach ($snapshotPlans as $snapPlan) {
            $snapId = (int) ($snapPlan['id'] ?? 0);
            $livePlan = $snapId ? $liveById->get($snapId) : null;

            if (! $livePlan) {
                $livePlan = $this->findMatchingLivePlan($livePlans, $snapPlan, $snapshotById);
            }

            if (! $livePlan) {
                $willRestoreMissing[] = $this->summarizePlanRow($snapPlan);

                continue;
            }

            $hasLiveActivity = $mergeService->planHasProductionActivity($livePlan);
            if ($hasLiveActivity) {
                $protectedActivePlans[] = $this->summarizePlanRow($livePlan->toArray(), [
                    'live_ok' => (float) $livePlan->ok,
                    'live_repair' => (float) $livePlan->repair,
                    'live_reject' => (float) $livePlan->reject,
                    'live_status' => $livePlan->status,
                ]);
            }

            $changedFields = $this->diffScheduleFields($livePlan, $snapPlan);
            if (! empty($changedFields)) {
                $willUpdateSchedule[] = [
                    'plan_id' => $livePlan->id,
                    'shift_name' => $snapPlan['shift_name'] ?? $livePlan->shift_name,
                    'press_name' => $snapPlan['press_name'] ?? $livePlan->press_name,
                    'row_no' => $snapPlan['row_no'] ?? $livePlan->row_no,
                    'job_master' => $snapPlan['job_master'] ?? $livePlan->job_master,
                    'job_no' => $snapPlan['job_no'] ?? $livePlan->job_no,
                    'changed_fields' => $changedFields,
                    'has_live_activity' => $hasLiveActivity,
                    'live_plan_qty' => (float) $livePlan->plan,
                    'backup_plan_qty' => (float) ($snapPlan['plan'] ?? 0),
                    'live_start' => $livePlan->start_time,
                    'backup_start' => $snapPlan['start_time'] ?? null,
                    'live_finish' => $livePlan->finish_time,
                    'backup_finish' => $snapPlan['finish_time'] ?? null,
                ];
            } else {
                $unchanged[] = $this->summarizePlanRow($snapPlan);
            }
        }

        foreach ($livePlans as $livePlan) {
            if (! $snapshotById->has($livePlan->id)) {
                $hasActivity = $mergeService->planHasProductionActivity($livePlan);
                $postSnapshotPlans[] = array_merge($this->summarizePlanRow($livePlan->toArray()), [
                    'has_activity' => $hasActivity,
                    'action_on_restore' => $hasActivity ? 'kept_protected' : 'removed_or_kept_by_mode',
                ]);
            }
        }

        $liveSignaturesCount = Schema::hasTable('signatures')
            ? Signature::whereDate('work_date', $parsedDate)->count()
            : 0;

        return [
            'backup_id' => $backup->id,
            'plan_date' => $parsedDate,
            'counts' => [
                'snapshot_plans' => $snapshotPlans->count(),
                'live_plans' => $livePlans->count(),
                'will_restore_missing' => count($willRestoreMissing),
                'will_update_schedule' => count($willUpdateSchedule),
                'unchanged' => count($unchanged),
                'post_snapshot_plans' => count($postSnapshotPlans),
                'protected_active_plans' => count($protectedActivePlans),
                'snapshot_signatures' => count($payload['signatures'] ?? []),
                'live_signatures' => $liveSignaturesCount,
            ],
            'will_restore_missing' => $willRestoreMissing,
            'will_update_schedule' => $willUpdateSchedule,
            'unchanged' => $unchanged,
            'post_snapshot_plans' => $postSnapshotPlans,
            'protected_active_plans' => $protectedActivePlans,
        ];
    }

    /**
     * Safely restore a backup snapshot inside a DB transaction.
     *
     * Rules:
     * 1. Automatic pre_restore backup is captured before any mutation.
     * 2. Snapshot ProductionPlans are restored (preserving original IDs whenever possible so JobMaster `-{planId}` links stay intact).
     * 3. Any plan or JobMaster that has production activity (OK/Repair/Reject, sessions, downtimes, dandoris, finished/running status)
     *    NEVER loses its actual production records, logs, sessions, or LKH signatures.
     * 4. Plans added AFTER the snapshot that have NO production activity are removed when $pruneUnlistedIdle is true (default true),
     *    while plans added after the snapshot that DO have production activity are preserved!
     * 5. Signatures in `signatures` table are additively restored (never deleting newer signatures).
     * 6. DashboardRealtimeService cache is flushed and lines are signaled.
     */
    public function restoreSnapshot(
        ScheduleBackup $backup,
        ?int $userId = null,
        bool $pruneUnlistedIdle = true
    ): array {
        $parsedDate = $backup->plan_date->toDateString();
        $payload = $backup->snapshot_payload ?? [];
        $snapshotPlans = collect($payload['production_plans'] ?? []);
        $snapshotJobs = collect($payload['job_masters'] ?? []);
        $snapshotSignatures = collect($payload['signatures'] ?? []);

        return DB::transaction(function () use (
            $backup,
            $parsedDate,
            $userId,
            $pruneUnlistedIdle,
            $snapshotPlans,
            $snapshotJobs,
            $snapshotSignatures
        ) {
            // 1. Capture safety backup before restoring
            $preRestoreBackup = $this->captureSnapshot($parsedDate, 'pre_restore', $userId, [
                'label' => 'Auto Pre-Restore before Backup #'.$backup->id,
                'restored_from_id' => $backup->id,
                'allow_empty' => true,
            ]);

            $mergeService = app(PpcScheduleMergeService::class);

            /** @var Collection<int, ProductionPlan> $livePlans */
            $livePlans = ProductionPlan::whereDate('plan_date', $parsedDate)->lockForUpdate()->get();
            $liveById = $livePlans->keyBy('id');
            $snapshotById = $snapshotPlans->keyBy('id');

            $restoredCount = 0;
            $updatedCount = 0;
            $preservedActiveCount = 0;
            $prunedIdleCount = 0;
            $retainedLivePlanIds = [];
            $planIdRemap = []; // oldSnapshotPlanId => currentPlanId

            $planColumns = Schema::getColumnListing('production_plans');
            $planColumnsMap = array_flip($planColumns);

            foreach ($snapshotPlans as $snapPlan) {
                $snapId = (int) ($snapPlan['id'] ?? 0);
                $livePlan = $snapId ? $liveById->get($snapId) : null;

                if (! $livePlan) {
                    $livePlan = $this->findMatchingLivePlan($livePlans, $snapPlan, $snapshotById);
                }

                if ($livePlan) {
                    $hasLiveActivity = $mergeService->planHasProductionActivity($livePlan);
                    $updateData = $this->filterColumns($snapPlan, $planColumnsMap, ['id', 'created_at', 'updated_at']);

                    if ($hasLiveActivity) {
                        foreach (self::PROTECTED_PLAN_FIELDS as $protectedField) {
                            unset($updateData[$protectedField]);
                        }
                        $preservedActiveCount++;
                    } else {
                        // Even if live has no activity, if snapshot had actuals, keep the higher/non-empty value
                        if (((float) ($snapPlan['ok'] ?? 0)) <= 0 && ((float) $livePlan->ok) > 0) {
                            foreach (self::PROTECTED_PLAN_FIELDS as $protectedField) {
                                unset($updateData[$protectedField]);
                            }
                        }
                    }

                    $livePlan->update($updateData);
                    $retainedLivePlanIds[(int) $livePlan->id] = true;
                    if ($snapId) {
                        $planIdRemap[$snapId] = (int) $livePlan->id;
                    }
                    $updatedCount++;
                } else {
                    // Plan was deleted after snapshot — recreate it (preserving original ID if unoccupied)
                    $insertData = $this->filterColumns($snapPlan, $planColumnsMap, ['created_at', 'updated_at']);
                    $canReuseId = $snapId > 0 && ! ProductionPlan::where('id', $snapId)->exists();

                    if ($canReuseId) {
                        $insertData['id'] = $snapId;
                        $insertData['created_at'] = now();
                        $insertData['updated_at'] = now();
                        DB::table('production_plans')->insert($insertData);
                        $newPlan = ProductionPlan::find($snapId);
                    } else {
                        unset($insertData['id']);
                        $newPlan = ProductionPlan::create($insertData);
                    }

                    if ($newPlan) {
                        $retainedLivePlanIds[(int) $newPlan->id] = true;
                        if ($snapId) {
                            $planIdRemap[$snapId] = (int) $newPlan->id;
                        }
                        $restoredCount++;
                    }
                }
            }

            // Handle live plans that were NOT in the snapshot
            foreach ($livePlans as $livePlan) {
                if (isset($retainedLivePlanIds[(int) $livePlan->id])) {
                    continue;
                }

                $hasActivity = $mergeService->planHasProductionActivity($livePlan);
                if ($hasActivity) {
                    // Never delete a plan that has production activity!
                    $preservedActiveCount++;

                    continue;
                }

                if ($pruneUnlistedIdle) {
                    // Also clean up any idle JobMaster linked to this pruned plan
                    $linkedJm = $mergeService->findLinkedJobMaster((int) $livePlan->id);
                    if ($linkedJm && ! $mergeService->jobHasActivity($linkedJm)) {
                        $linkedJm->delete();
                    }
                    $livePlan->delete();
                    $prunedIdleCount++;
                }
            }

            // 2. Restore / Sync linked JobMasters without losing any production data
            $jobColumns = Schema::getColumnListing('job_masters');
            $jobColumnsMap = array_flip($jobColumns);

            foreach ($snapshotJobs as $snapJob) {
                $snapJobId = (int) ($snapJob['id'] ?? 0);
                $oldJobNumber = (string) ($snapJob['job_number'] ?? '');

                $embeddedOldPlanId = null;
                if (preg_match('/-(\d+)$/', $oldJobNumber, $m)) {
                    $embeddedOldPlanId = (int) $m[1];
                }

                $mappedPlanId = ($embeddedOldPlanId && isset($planIdRemap[$embeddedOldPlanId]))
                    ? $planIdRemap[$embeddedOldPlanId]
                    : $embeddedOldPlanId;

                $targetPlan = $mappedPlanId ? ProductionPlan::find($mappedPlanId) : null;
                $expectedJobNumber = $targetPlan
                    ? $this->buildJobIdentifier($targetPlan)
                    : $oldJobNumber;

                $existingJob = null;
                if ($snapJobId > 0) {
                    $existingJob = JobMaster::find($snapJobId);
                }
                if (! $existingJob && $expectedJobNumber !== '') {
                    $existingJob = JobMaster::where('job_number', $expectedJobNumber)->first();
                }
                if (! $existingJob && $mappedPlanId) {
                    $existingJob = $mergeService->findLinkedJobMaster((int) $mappedPlanId);
                }

                if ($existingJob) {
                    $hasJobActivity = $mergeService->jobHasActivity($existingJob);
                    $jobUpdate = [
                        'job_name' => $targetPlan ? $targetPlan->job_master : ($snapJob['job_name'] ?? $existingJob->job_name),
                        'line' => $targetPlan ? $targetPlan->press_name : ($snapJob['line'] ?? $existingJob->line),
                        'target_qty' => (int) ($targetPlan ? $targetPlan->plan : ($snapJob['target_qty'] ?? $existingJob->target_qty)),
                        'sequence_no' => (int) ($targetPlan ? ($targetPlan->row_no ?: 1) : ($snapJob['sequence_no'] ?? $existingJob->sequence_no)),
                    ];
                    if ($targetPlan && $targetPlan->start_time) {
                        $jobUpdate['plan_start'] = Carbon::parse($parsedDate.' '.$targetPlan->start_time)->startOfMinute();
                    }
                    if ($targetPlan && $targetPlan->finish_time) {
                        $jobUpdate['plan_end'] = Carbon::parse($parsedDate.' '.$targetPlan->finish_time)->startOfMinute();
                    }

                    if (! $hasJobActivity && isset($snapJob['status'])) {
                        $jobUpdate['status'] = $snapJob['status'];
                    }

                    if ($expectedJobNumber !== '' && $existingJob->job_number !== $expectedJobNumber) {
                        $conflict = JobMaster::where('job_number', $expectedJobNumber)
                            ->where('id', '!=', $existingJob->id)
                            ->first();
                        if (! $conflict) {
                            $jobUpdate['job_number'] = $expectedJobNumber;
                        } elseif (! $mergeService->jobHasActivity($conflict)) {
                            $conflict->delete();
                            $jobUpdate['job_number'] = $expectedJobNumber;
                        }
                    }

                    $existingJob->update($jobUpdate);
                } else {
                    // Only recreate JobMaster if it had activity in the snapshot or its ID is referenced
                    $insertJob = $this->filterColumns($snapJob, $jobColumnsMap, ['created_at', 'updated_at']);
                    if ($expectedJobNumber !== '') {
                        $insertJob['job_number'] = $expectedJobNumber;
                    }
                    if ($snapJobId > 0 && ! JobMaster::where('id', $snapJobId)->exists()) {
                        $insertJob['id'] = $snapJobId;
                        $insertJob['created_at'] = now();
                        $insertJob['updated_at'] = now();
                        DB::table('job_masters')->insert($insertJob);
                    } else {
                        unset($insertJob['id']);
                        JobMaster::create($insertJob);
                    }
                }
            }

            // 3. Additively restore LKH Signatures (never overwrite or delete existing signatures)
            $restoredSignatures = 0;
            if (Schema::hasTable('signatures') && $snapshotSignatures->isNotEmpty()) {
                foreach ($snapshotSignatures as $sig) {
                    $exists = Signature::whereDate('work_date', $parsedDate)
                        ->where('role', $sig['role'] ?? '')
                        ->where('line_name', $sig['line_name'] ?? null)
                        ->where('shift_name', $sig['shift_name'] ?? null)
                        ->exists();

                    if (! $exists && ! empty($sig['signature_data'])) {
                        Signature::create([
                            'role' => $sig['role'],
                            'work_date' => $parsedDate,
                            'signature_data' => $sig['signature_data'],
                            'line_name' => $sig['line_name'] ?? null,
                            'shift_name' => $sig['shift_name'] ?? null,
                        ]);
                        $restoredSignatures++;
                    }
                }
            }

            // 4. Record audit entry in schedule_revisions and update backup metadata
            if ($userId && Schema::hasTable('schedule_revisions')) {
                ScheduleRevision::create([
                    'plan_date' => $parsedDate,
                    'shift_name' => $backup->shift_scope ?: 'all',
                    'action' => 'restore',
                    'snapshot_before' => [
                        'pre_restore_backup_id' => $preRestoreBackup?->id,
                    ],
                    'snapshot_after' => [
                        'restored_from_backup_id' => $backup->id,
                        'restored_count' => $restoredCount,
                        'updated_count' => $updatedCount,
                        'preserved_active_count' => $preservedActiveCount,
                        'pruned_idle_count' => $prunedIdleCount,
                        'restored_signatures' => $restoredSignatures,
                    ],
                    'created_by' => $userId,
                ]);
            }

            $backup->update([
                'last_restored_at' => now(),
                'last_restored_by' => $userId,
            ]);

            // 5. Flush DashboardRealtimeService static cache and signal all lines
            DashboardRealtimeService::flushCache();
            foreach (['PRESS A', 'PRESS B', 'PRESS C', 'PRESS D', 'Line A', 'Line B', 'Line C', 'Line D'] as $lineName) {
                DashboardRealtimeService::signalUpdate($lineName);
            }

            return [
                'backup_id' => $backup->id,
                'pre_restore_backup_id' => $preRestoreBackup?->id,
                'plan_date' => $parsedDate,
                'restored_count' => $restoredCount,
                'updated_count' => $updatedCount,
                'preserved_active_count' => $preservedActiveCount,
                'pruned_idle_count' => $prunedIdleCount,
                'restored_signatures' => $restoredSignatures,
            ];
        });
    }

    /**
     * Find all JobMasters linked to the given ProductionPlan IDs via `-{planId}` suffix.
     */
    public function findConnectedJobMasters(array $planIds): Collection
    {
        if (empty($planIds)) {
            return collect();
        }

        return JobMaster::where(function ($query) use ($planIds) {
            foreach ($planIds as $planId) {
                $query->orWhere('job_number', 'LIKE', "%-{$planId}");
            }
        })->get()->filter(function (JobMaster $jm) use ($planIds) {
            return (bool) preg_match('/-(\d+)$/', (string) $jm->job_number, $m)
                && in_array((int) $m[1], $planIds, true);
        })->values();
    }

    private function findMatchingLivePlan(Collection $livePlans, array $snapPlan, Collection $snapshotById): ?ProductionPlan
    {
        $snapShift = strtolower(trim((string) ($snapPlan['shift_name'] ?? '')));
        $snapPress = strtoupper(trim((string) ($snapPlan['press_name'] ?? '')));
        $snapRowType = (string) ($snapPlan['row_type'] ?? 'job');
        $snapJobNo = strtoupper(trim((string) ($snapPlan['job_no'] ?? '')));
        $snapJobMaster = strtoupper(trim((string) ($snapPlan['job_master'] ?? '')));
        $snapRowNo = (int) ($snapPlan['row_no'] ?? 0);

        return $livePlans->first(function (ProductionPlan $live) use (
            $snapshotById,
            $snapShift,
            $snapPress,
            $snapRowType,
            $snapJobNo,
            $snapJobMaster,
            $snapRowNo
        ) {
            // Do not steal a live plan that already corresponds by ID to another snapshot row
            if ($snapshotById->has($live->id)) {
                return false;
            }

            if (strtolower(trim((string) $live->shift_name)) !== $snapShift) {
                return false;
            }
            if (strtoupper(trim((string) $live->press_name)) !== $snapPress) {
                return false;
            }
            if ((string) $live->row_type !== $snapRowType) {
                return false;
            }

            return strtoupper(trim((string) $live->job_no)) === $snapJobNo
                && strtoupper(trim((string) $live->job_master)) === $snapJobMaster
                && (int) $live->row_no === $snapRowNo;
        });
    }

    private function diffScheduleFields(ProductionPlan $livePlan, array $snapPlan): array
    {
        $compareFields = [
            'shift_name',
            'press_name',
            'row_no',
            'row_type',
            'job_master',
            'job_no',
            'plan',
            'ct_detik',
            'process_time',
            'tpt',
            'gsph_item',
            'start_time',
            'finish_time',
            'keterangan',
        ];

        $changed = [];
        foreach ($compareFields as $field) {
            if (! array_key_exists($field, $snapPlan)) {
                continue;
            }
            $liveVal = $livePlan->getAttribute($field);
            $snapVal = $snapPlan[$field];

            if (is_numeric($liveVal) && is_numeric($snapVal)) {
                if ((float) $liveVal !== (float) $snapVal) {
                    $changed[] = $field;
                }

                continue;
            }

            if (trim((string) ($liveVal ?? '')) !== trim((string) ($snapVal ?? ''))) {
                $changed[] = $field;
            }
        }

        return $changed;
    }

    private function summarizePlanRow(array $plan, array $extra = []): array
    {
        return array_merge([
            'id' => $plan['id'] ?? null,
            'shift_name' => $plan['shift_name'] ?? '-',
            'press_name' => $plan['press_name'] ?? '-',
            'row_no' => $plan['row_no'] ?? 0,
            'row_type' => $plan['row_type'] ?? 'job',
            'job_master' => $plan['job_master'] ?? '-',
            'job_no' => $plan['job_no'] ?? '-',
            'plan' => (float) ($plan['plan'] ?? 0),
            'ok' => (float) ($plan['ok'] ?? 0),
            'repair' => (float) ($plan['repair'] ?? 0),
            'reject' => (float) ($plan['reject'] ?? 0),
            'start_time' => $plan['start_time'] ?? null,
            'finish_time' => $plan['finish_time'] ?? null,
            'status' => $plan['status'] ?? 'pending',
        ], $extra);
    }

    private function serializeModel($model): array
    {
        $attrs = $model->getAttributes();
        foreach ($attrs as $k => $v) {
            if ($v instanceof \DateTimeInterface) {
                $attrs[$k] = $v->format('Y-m-d H:i:s');
            }
        }

        return $attrs;
    }

    private function filterColumns(array $data, array $allowedColumnsMap, array $exclude = []): array
    {
        $filtered = [];
        $excludeMap = array_flip($exclude);

        foreach ($data as $key => $val) {
            if (isset($excludeMap[$key]) || ! isset($allowedColumnsMap[$key])) {
                continue;
            }
            if ($key === 'plan_date' && is_string($val) && strlen($val) >= 10) {
                $val = substr($val, 0, 10);
            }
            $filtered[$key] = $val;
        }

        return $filtered;
    }

    private function buildJobIdentifier(ProductionPlan $plan): string
    {
        $jobNo = trim((string) $plan->job_no);
        if ($jobNo === '') {
            $jobNo = 'AUTO-'.(Str::slug((string) $plan->job_master) ?: 'JOB');
        }

        return $jobNo.'-'.$plan->id;
    }

    private function defaultLabel(string $backupType, string $parsedDate, ?string $sourceFilename): string
    {
        $typeLabel = match ($backupType) {
            'initial' => 'Initial Schedule Snapshot',
            'pre_import' => 'Pre-Import Snapshot',
            'pre_restore' => 'Pre-Restore Safety Snapshot',
            'pre_clear' => 'Pre-Clear Safety Snapshot',
            'manual' => 'Manual Backup Snapshot',
            default => ucfirst(str_replace('_', ' ', $backupType)),
        };

        if ($sourceFilename) {
            return "{$typeLabel} ({$sourceFilename})";
        }

        return "{$typeLabel} ({$parsedDate})";
    }
}
