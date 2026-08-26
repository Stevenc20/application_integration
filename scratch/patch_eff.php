<?php
$file = 'app/Services/ProductionService.php';
$content = file_get_contents($file);

// 1. Add canonical method
$newMethod = <<< 'PHP'
    /**
     * Calculate canonical efficiency / achievement metric.
     */
    public function calculateEfficiency($actual, $target)
    {
        if (empty($target) || $target <= 0) {
            return 0;
        }
        return round(((float)$actual / (float)$target) * 100, 2);
    }

    /**
     * Delete a downtime log.
PHP;
$content = str_replace("    /**\n     * Delete a downtime log.", $newMethod, $content);

// 2. Fix logProduction (line 279)
$searchLog = <<< 'PHP'
            $job = JobMaster::find($jobId);
            $targetQty = $job?->capacity ?? 0;
            
            $efficiency = 0;
            if ($targetQty > 0) {
                $efficiency = round(($actualQty / $targetQty) * 100, 2);
            }
PHP;
$replaceLog = <<< 'PHP'
            $job = JobMaster::find($jobId);
            $targetQty = $job?->target_qty ?? 0; // Use canonical target
            
            $efficiency = $this->calculateEfficiency($actualQty, $targetQty);
PHP;
$content = str_replace($searchLog, $replaceLog, $content);

// 3. Fix restartJob (line 485)
$searchRestart = <<< 'PHP'
                    'actual_reject'   => $totalReject,
                    'efficiency'      => ($job && $job->capacity > 0) ? ($totalOk / $job->capacity) * 100 : 0
                ]
PHP;
$replaceRestart = <<< 'PHP'
                    'actual_reject'   => $totalReject,
                    'efficiency'      => $this->calculateEfficiency($totalOk, $job?->target_qty ?? 0)
                ]
PHP;
$content = str_replace($searchRestart, $replaceRestart, $content);

// 4. Fix saveDailyProduction (line 992)
$searchSave = <<< 'PHP'
            $job = JobMaster::find($jobId);

            $targetQty = $job?->capacity ?? 0;
            $actualQty = (int) ($data['actual_qty'] ?? 0);

            $efficiency = 0;
            if ($targetQty > 0) {
                $efficiency = round(($actualQty / $targetQty) * 100, 2);
            }
PHP;
$replaceSave = <<< 'PHP'
            $job = JobMaster::find($jobId);

            $targetQty = $job?->target_qty ?? 0; // Use canonical target
            $actualQty = (int) ($data['actual_qty'] ?? 0);

            $efficiency = $this->calculateEfficiency($actualQty, $targetQty);
PHP;
$content = str_replace($searchSave, $replaceSave, $content);

file_put_contents($file, $content);
echo "Patched ProductionService.php\n";
