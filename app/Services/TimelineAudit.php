<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TimelineAudit
{
    protected static $correlationId = null;
    protected static $context = [];
    protected static $startTime = null;
    protected static $lastTime = null;

    public static function isEnabled()
    {
        return env('TIMELINE_AUDIT', true);
    }

    public static function beginTrace($action, $context = [])
    {
        if (!self::isEnabled()) return;
        self::$correlationId = strtoupper($action) . '-' . now()->format('Ymd-His') . '-' . strtoupper(Str::random(4));
        self::$context = $context;
        self::$startTime = microtime(true);
        self::$lastTime = self::$startTime;
        
        Log::info("[AUDIT:" . self::$correlationId . "] BEGIN $action", $context);
    }

    public static function getCorrelationId()
    {
        return self::$correlationId ?? 'NO-TRACE';
    }

    public static function log($message, $data = [])
    {
        if (!self::isEnabled()) return;
        
        $now = microtime(true);
        $duration = self::$lastTime ? round(($now - self::$lastTime) * 1000) . 'ms' : '0ms';
        self::$lastTime = $now;

        $prefix = "[AUDIT:" . self::getCorrelationId() . "]";
        $data['duration'] = $duration;

        Log::info("$prefix $message", $data);
    }

    public static function logError(\Throwable $e, $additionalContext = [])
    {
        if (!self::isEnabled()) return;

        $data = array_merge(self::$context, $additionalContext);
        $data['exception'] = $e->getMessage();
        $data['trace'] = $e->getTraceAsString();

        Log::error("[AUDIT:" . self::getCorrelationId() . "] ERROR", $data);
    }

    public static function logDelete($id, $job, $caller, $reason)
    {
        self::log("DELETE PLAN", [
            'id' => $id,
            'job' => $job,
            'caller' => $caller,
            'reason' => $reason
        ]);
    }

    public static function logUpdate($id, $job, $changes, $caller)
    {
        self::log("UPDATE PLAN", [
            'id' => $id,
            'job' => $job,
            'changes' => $changes,
            'caller' => $caller
        ]);
    }

    public static function logSplit($parentId, $childId, $reason)
    {
        self::log("SPLIT", [
            'parent' => $parentId,
            'child' => $childId,
            'reason' => $reason
        ]);
    }

    public static function logBreak($start, $finish)
    {
        self::log("CREATE BREAK", [
            'start' => $start,
            'finish' => $finish
        ]);
    }

    public static function logCursor($before, $after, $jobName)
    {
        self::log("CURSOR MOVE", [
            'before' => $before,
            'after' => $after,
            'job' => $jobName
        ]);
    }

    public static function logStats($label, $date, $shifts)
    {
        if (!self::isEnabled()) return;
        
        $query = \App\Models\ProductionPlan::whereDate('plan_date', $date)
            ->whereIn('shift_name', $shifts);

        $hash = 'none';
        $stats = (clone $query)->selectRaw("
                COUNT(*) as total_rows,
                SUM(plan) as total_plan,
                SUM(CASE WHEN row_type = 'job' THEN 1 ELSE 0 END) as count_job,
                SUM(CASE WHEN row_type = 'break' THEN 1 ELSE 0 END) as count_break,
                SUM(CASE WHEN row_type = 'note' THEN 1 ELSE 0 END) as count_note,
                SUM(CASE WHEN source_type = 'recovery' THEN 1 ELSE 0 END) as count_recovery
            ")->first();

        $duplicates = (clone $query)->where('row_type', 'job')
            ->selectRaw("job_no, COUNT(*) as jumlah, SUM(plan) as total_plan")
            ->groupBy('job_no')
            ->havingRaw("COUNT(*) > 1")
            ->get();

        $rows = isset($details) ? $details->toArray() : [];
        self::log("STATS: $label", [
            'date' => $date,
            'shifts' => $shifts,
            'stats' => $stats ? $stats->toArray() : [],
            'duplicates' => $duplicates->toArray(),
            'hash' => $hash ?? 'none',
            'rows' => $rows
        ]);
    }

    public static function logDatasetHash($label, $plans)
    {
        if (!self::isEnabled()) return;

        $details = collect($plans)->map(function($row) {
            return [
                'row_no' => $row->row_no,
                'id' => $row->id,
                'job' => $row->job_no,
                'row_type' => $row->row_type,
                'source_type' => $row->source_type,
                'plan' => $row->plan,
                'process_time' => $row->process_time,
                'tpt' => $row->tpt,
                'start_time' => $row->start_time,
                'finish_time' => $row->finish_time,
                'parent' => $row->parent_job_id,
                'recovery' => $row->recovery_id
            ];
        });

        $hash = md5(json_encode($details));

        self::log("DATASET FINGERPRINT: $label", [
            'count' => count($plans),
            'hash' => $hash,
            'rows' => $details->toArray()
        ]);
    }

    public static function dumpSequence($plans)
    {
        if (!self::isEnabled()) return;
        $dump = [];
        foreach ($plans as $p) {
            $dump[] = [
                'ROW' => $p->row_no,
                'ID' => $p->id,
                'JOB' => $p->job_no,
                'PLAN' => $p->plan,
                'START' => $p->start_time,
                'FINISH' => $p->finish_time,
                'SOURCE' => $p->source_type,
                'PARENT' => $p->parent_job_id,
                'RECOVERY_ID' => $p->recovery_id
            ];
        }
        self::log("FINAL SEQUENCE DUMP", ['count' => count($dump), 'sequence' => $dump]);
    }
}
