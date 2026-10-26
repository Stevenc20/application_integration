<?php

namespace App\Services;

use App\Models\ProductionPlan;
use App\Models\ProductionPlanDuplicateAudit;
use App\Models\RecoveryItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Read-only duplicate detection for PPC production plans plus a guarded,
 * single-record delete that never touches merge/upload/recovery logic.
 */
class ProductionPlanDuplicateService
{
    /**
     * Statuses that are still safe to clean up. Anything else (approved,
     * completed, running, ...) needs manual review.
     */
    public const DELETABLE_STATUSES = ['pending', 'draft'];

    private const NON_JOB_LABELS = [
        'BREAK',
        'ISTIRAHAT',
        'TOTAL',
        'TOTAL FINISH',
        'TOTAL FNISH',
        'FINISH',
        'TOTAL PCS',
    ];

    public function __construct(private PpcScheduleMergeService $mergeService) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function scan(?string $date = null, ?string $press = null, ?string $shift = null): array
    {
        $groups = [];

        foreach ($this->query($date, $press, $shift)->get() as $plan) {
            $normalized = $this->normalize($plan);
            if ($normalized === null) {
                continue;
            }

            $groups[$normalized['key']][] = ['plan' => $plan, 'normalized' => $normalized];
        }

        $result = [];

        foreach ($groups as $members) {
            if (count($members) < 2) {
                continue;
            }

            $group = $this->buildGroup($members);
            if ($group !== null) {
                $result[] = $group;
            }
        }

        usort($result, fn (array $a, array $b) => strcmp($a['sort_key'], $b['sort_key']));

        foreach ($result as &$group) {
            unset($group['sort_key']);
        }
        unset($group);

        return $result;
    }

    public function detail(int $planId): ?array
    {
        $plan = ProductionPlan::find($planId);
        if (! $plan) {
            return null;
        }

        $normalized = $this->normalize($plan);
        if ($normalized === null) {
            return [
                'plan' => $this->planPayload($plan),
                'analysis' => null,
                'peers' => [],
                'message' => 'Item ini bukan baris job yang dapat diperiksa sebagai duplikat.',
            ];
        }

        $peers = [];
        foreach ($this->query($normalized['plan_date'], $plan->press_name, $plan->shift_name)->get() as $candidate) {
            if ($candidate->id === $plan->id) {
                continue;
            }

            $candidateNormalized = $this->normalize($candidate);
            if ($candidateNormalized === null || $candidateNormalized['key'] !== $normalized['key']) {
                continue;
            }

            $peers[] = [
                'plan' => $this->planPayload($candidate),
                'analysis' => $this->analyze($candidate, $candidateNormalized, null, $plan),
            ];
        }

        return [
            'plan' => $this->planPayload($plan),
            'analysis' => $this->analyze($plan, $normalized),
            'peers' => $peers,
        ];
    }

    /**
     * Delete exactly one selected duplicate. The peer is only used to prove the
     * pair shares the same schedule slot; it is never deleted.
     *
     * @return array{success: bool, code: string, message: string, blockers?: array<int, string>, deleted_id?: int, kept_id?: int}
     */
    public function deleteDuplicate(
        int $planId,
        int $peerId,
        string $reason,
        ?int $userId,
        bool $confirmReview = false
    ): array {
        $plan = ProductionPlan::find($planId);
        $peer = ProductionPlan::find($peerId);

        if (! $plan || ! $peer) {
            return $this->failure('not_found', 'Item atau pasangan duplikat tidak ditemukan.');
        }

        if ((int) $plan->id === (int) $peer->id) {
            return $this->failure('invalid_pair', 'Item yang dihapus dan pasangannya tidak boleh sama.');
        }

        $normalized = $this->normalize($plan);
        $peerNormalized = $this->normalize($peer);

        if ($normalized === null || $peerNormalized === null || $normalized['key'] !== $peerNormalized['key']) {
            return $this->failure('not_duplicate', 'Kedua item tidak berada pada slot jadwal yang sama, jadi tidak dianggap duplikat.');
        }

        $analysis = $this->analyze($plan, $normalized, null, $peer);
        $peerAnalysis = $this->analyze($peer, $peerNormalized, null, $plan);

        if ($analysis['is_recovery']) {
            return $this->failure('recovery_item', 'Item berasal dari jadwal Recovery sehingga tidak boleh dihapus dari sini. Periksa melalui menu Recovery.', $analysis['blockers']);
        }

        if ($peerAnalysis['is_recovery']) {
            return $this->failure('recovery_peer', 'Pasangan duplikat berasal dari Recovery. Periksa kedua item secara manual sebelum menghapus.', $analysis['blockers']);
        }

        if ($analysis['blockers'] !== []) {
            return $this->failure('blocked', 'Item tidak dapat dihapus karena masih terhubung dengan aktivitas atau data lain.', $analysis['blockers']);
        }

        if ($this->pairRequiresReview($plan, $peer) && ! $confirmReview) {
            return $this->failure('needs_review', 'Kedua item memiliki detail jadwal yang tidak identik. Konfirmasi pemeriksaan manual diperlukan sebelum menghapus.', $analysis['warnings']);
        }

        if ($analysis['has_activity'] && $peerAnalysis['has_activity']) {
            return $this->failure('both_active', 'Kedua item sudah memiliki progres produksi. Pemeriksaan manual diperlukan; tidak ada item yang dihapus.', ['Kedua item memiliki aktivitas produksi.']);
        }

        $deleted = false;

        try {
            $deleted = DB::transaction(function () use ($planId, $peer, $reason, $userId) {
                $lockedPlan = ProductionPlan::whereKey($planId)->lockForUpdate()->first();

                if (! $lockedPlan) {
                    return false;
                }

                $snapshot = $lockedPlan->toArray();
                $snapshot['duplicate_of_id'] = $peer->id;
                $snapshot['duplicate_of_job_no'] = $peer->job_no;
                $snapshot['duplicate_of_job_master'] = $peer->job_master;

                if (! $lockedPlan->delete() || ProductionPlan::whereKey($planId)->exists()) {
                    return false;
                }

                $audit = ProductionPlanDuplicateAudit::create([
                    'production_plan_id' => $planId,
                    'duplicate_of_id' => $peer->id,
                    'action' => 'deleted',
                    'job_no' => $lockedPlan->job_no,
                    'job_master' => $lockedPlan->job_master,
                    'plan_date' => $lockedPlan->plan_date,
                    'shift_name' => $lockedPlan->shift_name,
                    'press_name' => $lockedPlan->press_name,
                    'start_time' => $lockedPlan->start_time,
                    'finish_time' => $lockedPlan->finish_time,
                    'plan_qty' => (float) ($lockedPlan->plan ?? $lockedPlan->target_qty ?? 0),
                    'reason' => $reason,
                    'snapshot' => $snapshot,
                    'deleted_by' => $userId,
                    'deleted_at' => now(),
                ]);

                if (! $audit->exists) {
                    throw new \RuntimeException('Audit penghapusan duplikat gagal disimpan.');
                }

                return true;
            });
        } catch (QueryException $e) {
            return $this->failure('constraint', 'Item tidak dapat dihapus karena masih direferensikan oleh data lain. Periksa relasi produksi/recovery terlebih dahulu.');
        } catch (Throwable $e) {
            report($e);

            return $this->failure('failed', 'Terjadi kesalahan saat menghapus item. Tidak ada data yang diubah.');
        }

        if (! $deleted || ProductionPlan::whereKey($planId)->exists()) {
            return $this->failure('not_deleted', 'Item tidak terhapus dan tidak ada perubahan data yang disimpan. Muat ulang daftar lalu coba lagi.');
        }

        return [
            'success' => true,
            'code' => 'deleted',
            'message' => 'Item duplikat #'.$planId.' berhasil dihapus. Pasangan duplikat #'.$peerId.' tetap dipertahankan.',
            'deleted_id' => $planId,
            'kept_id' => $peerId,
        ];
    }

    private function query(?string $date, ?string $press, ?string $shift): Builder
    {
        return ProductionPlan::query()
            ->when(filled($date), fn (Builder $q) => $q->whereDate('plan_date', $date))
            ->when(filled($press) && strtoupper(trim((string) $press)) !== 'ALL', fn (Builder $q) => $q->whereRaw('UPPER(press_name) = ?', [strtoupper(trim((string) $press))]))
            ->when(filled($shift) && strtoupper(trim((string) $shift)) !== 'ALL', fn (Builder $q) => $q->where('shift_name', 'like', '%'.trim((string) $shift).'%'))
            ->orderBy('plan_date')
            ->orderBy('press_name')
            ->orderBy('shift_name')
            ->orderBy('start_time')
            ->orderBy('id');
    }

    private function normalize(ProductionPlan $plan): ?array
    {
        $rowType = strtolower(trim((string) $plan->row_type));
        if (in_array($rowType, ['break', 'total', 'header'], true)) {
            return null;
        }

        $jobNo = $this->normalizeKey($plan->job_no);
        $jobMaster = $this->normalizeKey($plan->job_master);

        if ($jobNo === '' && $jobMaster === '') {
            return null;
        }
        if ($jobNo !== '' && in_array($jobNo, self::NON_JOB_LABELS, true)) {
            return null;
        }

        $planDate = $plan->plan_date
            ? Carbon::parse($plan->plan_date)->toDateString()
            : '';
        if ($planDate === '') {
            return null;
        }

        $start = $this->normalizeTime($plan->start_time);
        if ($start === '') {
            return null;
        }

        $shift = $this->normalizeShift($plan->shift_name);
        $press = $this->normalizeKey($plan->press_name);
        if ($press === '') {
            $press = 'LINE-'.(int) $plan->line_master_id;
        }

        $finish = $this->normalizeTime($plan->finish_time);

        return [
            'key' => implode('||', [$planDate, $shift, $press, $jobNo, $jobMaster, $start]),
            'plan_date' => $planDate,
            'shift' => $shift,
            'press' => $press,
            'job_no' => $jobNo,
            'job_master' => $jobMaster,
            'start_time' => $start,
            'finish_time' => $finish,
            'split' => trim((string) $plan->split_group).'|'.trim((string) $plan->session_no),
        ];
    }

    private function buildGroup(array $members): ?array
    {
        $first = $members[0]['normalized'];
        $finishes = [];
        $splits = [];

        foreach ($members as $member) {
            $finishes[$member['normalized']['finish_time']] = true;
            $splits[$member['normalized']['split']] = true;
        }

        $confidence = (count($finishes) === 1 && count($splits) === 1) ? 'exact' : 'review';
        $analyses = [];
        $deletableCount = 0;
        $activityCount = 0;
        $recoveryCount = 0;

        foreach ($members as $member) {
            $analysis = $this->analyze($member['plan'], $member['normalized'], $confidence);
            $analyses[] = $analysis;
            $deletableCount += $analysis['deletable'] ? 1 : 0;
            $activityCount += $analysis['has_activity'] ? 1 : 0;
            $recoveryCount += $analysis['is_recovery'] ? 1 : 0;
        }

        $allIds = array_map(fn (array $analysis) => (int) $analysis['id'], $analyses);
        foreach ($analyses as &$analysis) {
            $analysis['peer_ids'] = array_values(array_filter($allIds, fn (int $id) => $id !== (int) $analysis['id']));
        }
        unset($analysis);

        return [
            'key' => $first['key'],
            'sort_key' => implode('||', [$first['plan_date'], $first['press'], $first['shift'], $first['start_time'], $first['job_no']]),
            'plan_date' => $first['plan_date'],
            'shift' => $first['shift'],
            'press' => $first['press'],
            'job_no' => $first['job_no'],
            'job_master' => $first['job_master'],
            'start_time' => $first['start_time'],
            'finish_times' => array_values(array_filter(array_keys($finishes), fn ($value) => $value !== '')),
            'confidence' => $confidence,
            'count' => count($members),
            'deletable_count' => $deletableCount,
            'has_activity_count' => $activityCount,
            'recovery_count' => $recoveryCount,
            'members' => $analyses,
        ];
    }

    private function analyze(
        ProductionPlan $plan,
        array $normalized,
        ?string $confidence = null,
        ?ProductionPlan $peer = null
    ): array {
        $hasActivity = $this->mergeService->planHasProductionActivity($plan);
        $isRecovery = strtolower((string) $plan->source_type) === 'recovery'
            || ! empty($plan->recovery_id);

        $status = strtolower(trim((string) $plan->status));
        $blockers = [];
        $warnings = [];

        if ($isRecovery) {
            $blockers[] = 'Item berasal dari jadwal Recovery.';
        }

        if (! in_array($status, self::DELETABLE_STATUSES, true)) {
            $blockers[] = 'Status item "'.($status !== '' ? $status : '-').'" bukan pending, sehingga perlu pemeriksaan manual.';
        }

        if ($hasActivity) {
            $blockers[] = 'Item sudah memiliki aktivitas atau aktual produksi.';
        }

        foreach ($this->referenceBlockers($plan) as $blocker) {
            $blockers[] = $blocker;
        }

        $pairReview = $confidence === 'review';

        if ($peer !== null && $this->pairRequiresReview($plan, $peer)) {
            $pairReview = true;
            $warnings[] = 'Detail finish time / split berbeda dengan pasangan duplikat.';
        }

        if (trim((string) $plan->split_group) !== '') {
            $warnings[] = 'Item merupakan bagian dari grup split job.';
        }

        if ($peer !== null && $this->mergeService->planHasProductionActivity($peer)) {
            $warnings[] = 'Pasangan duplikat sudah memiliki aktivitas produksi; item ini tetap dipertahankan.';
        }

        $deletable = $blockers === [];

        return array_merge($this->planPayload($plan), [
            'has_activity' => $hasActivity,
            'is_recovery' => $isRecovery,
            'deletable' => $deletable,
            'requires_review' => $pairReview,
            'blockers' => $blockers,
            'warnings' => $warnings,
            'peer_ids' => [],
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function referenceBlockers(ProductionPlan $plan): array
    {
        $blockers = [];
        $id = (int) $plan->id;

        if (ProductionPlan::where('parent_job_id', $id)->exists()) {
            $blockers[] = 'Item memiliki sesi split turunan.';
        }

        if (Schema::hasTable('recovery_items') && RecoveryItem::where('production_plan_id', $id)->exists()) {
            $blockers[] = 'Item terhubung ke data Recovery.';
        }

        $jobMaster = $this->mergeService->findLinkedJobMaster($id);
        if ($jobMaster !== null && ($jobMaster->repairRejects()->exists() || $jobMaster->qChecks()->exists())) {
            $blockers[] = 'Item terhubung ke data repair/reject atau Q-Check.';
        }

        if (Schema::hasTable('lkh_corrections') && DB::table('lkh_corrections')->where('plan_id', $id)->exists()) {
            $blockers[] = 'Item memiliki riwayat koreksi LKH.';
        }

        if (Schema::hasTable('job_finish_reasons') && DB::table('job_finish_reasons')->where('production_plan_id', $id)->exists()) {
            $blockers[] = 'Item memiliki alasan finish job.';
        }

        if (Schema::hasTable('pull_ahead_requests') && DB::table('pull_ahead_requests')
            ->where(function ($query) use ($id) {
                $query->where('original_plan_id', $id)->orWhere('new_plan_id', $id);
            })
            ->exists()) {
            $blockers[] = 'Item terhubung ke permintaan pull ahead.';
        }

        if (Schema::hasTable('production_schedules')
            && Schema::hasColumn('production_schedules', 'ppc_plan_id')
            && DB::table('production_schedules')->where('ppc_plan_id', $id)->exists()) {
            $blockers[] = 'Item terhubung ke data production schedule.';
        }

        return $blockers;
    }

    private function pairRequiresReview(ProductionPlan $plan, ProductionPlan $peer): bool
    {
        $a = $this->normalize($plan);
        $b = $this->normalize($peer);

        if ($a === null || $b === null) {
            return true;
        }

        return $a['finish_time'] !== $b['finish_time'] || $a['split'] !== $b['split'];
    }

    private function planPayload(ProductionPlan $plan): array
    {
        return [
            'id' => (int) $plan->id,
            'job_no' => $plan->job_no,
            'job_master' => $plan->job_master,
            'plan_date' => $plan->plan_date ? Carbon::parse($plan->plan_date)->toDateString() : null,
            'shift_name' => $plan->shift_name,
            'press_name' => $plan->press_name,
            'row_no' => $plan->row_no,
            'row_type' => $plan->row_type,
            'start_time' => $plan->start_time,
            'finish_time' => $plan->finish_time,
            'plan_qty' => (float) ($plan->plan ?? $plan->target_qty ?? 0),
            'target_qty' => (float) ($plan->target_qty ?? 0),
            'ok' => (float) ($plan->ok ?? 0),
            'repair' => (float) ($plan->repair ?? 0),
            'reject' => (float) ($plan->reject ?? 0),
            'status' => $plan->status,
            'source_type' => $plan->source_type,
            'recovery_id' => $plan->recovery_id,
            'split_group' => $plan->split_group,
            'session_no' => $plan->session_no,
            'keterangan' => $plan->keterangan,
            'created_at' => optional($plan->created_at)->toDateTimeString(),
            'updated_at' => optional($plan->updated_at)->toDateTimeString(),
        ];
    }

    private function normalizeKey($value): string
    {
        $value = strtoupper(trim((string) $value));
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/^(\d+)\.0+$/', '$1', $value);
        $value = str_replace(["\xc2\xa0", "\u{00A0}"], ' ', $value);

        return trim(preg_replace('/\s+/', ' ', $value));
    }

    private function normalizeShift($value): string
    {
        $shift = strtoupper(trim((string) $value));
        $shift = preg_replace('/\s*\(.*?REV.*?\)|\s*REV(?:ISI)?\b/i', '', $shift);
        $shift = preg_replace('/\bSHIFT\s*/i', '', $shift);
        $shift = trim(preg_replace('/\s+/', ' ', $shift));

        if (str_contains($shift, 'MALAM') || str_contains($shift, 'NIGHT') || $shift === '2') {
            return 'MALAM';
        }
        if (str_contains($shift, 'PAGI') || str_contains($shift, 'DAY') || $shift === '1') {
            return 'PAGI';
        }

        return $shift !== '' ? $shift : 'PAGI';
    }

    private function normalizeTime($value): string
    {
        $time = trim((string) $value);
        if ($time === '') {
            return '';
        }

        if (is_numeric($time) && (float) $time >= 0 && (float) $time < 1) {
            $minutes = (int) round((float) $time * 24 * 60) % (24 * 60);

            return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
        }

        if (preg_match('/(\d{1,2})[:.](\d{2})/', $time, $matches)) {
            $hour = (int) $matches[1];
            $minute = (int) $matches[2];
            if ($hour <= 23 && $minute <= 59) {
                return sprintf('%02d:%02d', $hour, $minute);
            }
        }

        return strtoupper($time);
    }

    /**
     * @param  array<int, string>  $blockers
     */
    private function failure(string $code, string $message, array $blockers = []): array
    {
        return [
            'success' => false,
            'code' => $code,
            'message' => $message,
            'blockers' => array_values($blockers),
        ];
    }
}
