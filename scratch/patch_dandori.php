<?php
$f = 'resources/js/operational/production-engine.js';
$c = file_get_contents($f);
$c = str_replace("\r\n", "\n", $c);

// Fix the gap before running downtimes
$s1 = <<< 'JS'
        if (runningDowntimesForJob.length > 0) {
            runningDowntimesForJob.forEach((rdItem, rdIdx) => {
                const rdStart = Math.max(rdItem.start.getTime(), lastPos);
                if (rdStart > lastPos && effectiveActualStart && rdStart > effectiveActualStart) {
                    const segStart = Math.max(lastPos, effectiveActualStart);
                    appendProduction(segStart, rdStart);
                }
JS;
$r1 = <<< 'JS'
        if (runningDowntimesForJob.length > 0) {
            runningDowntimesForJob.forEach((rdItem, rdIdx) => {
                const rdStart = Math.max(rdItem.start.getTime(), lastPos);
                if (rdStart > lastPos) {
                    if (effectiveActualStart && rdStart > effectiveActualStart) {
                        const segStart = Math.max(lastPos, effectiveActualStart);
                        appendProduction(segStart, rdStart);
                    } else if (!effectiveActualStart && hasDandori) {
                        addSegment(lastPos, rdStart, 'bg-amber-400', 'Dandori');
                    }
                }
JS;
$c = str_replace($s1, $r1, $c);

// Fix the final block when no running downtimes exist
$s2 = <<< 'JS'
        } else {
            if (finalTime > lastPos) {
                const segStart = (lastPos < effectiveActualStart) ? effectiveActualStart : lastPos;
                appendProduction(segStart, finalTime);
            }
        }
JS;
$r2 = <<< 'JS'
        } else {
            if (finalTime > lastPos) {
                if (effectiveActualStart) {
                    const segStart = (lastPos < effectiveActualStart) ? effectiveActualStart : lastPos;
                    appendProduction(segStart, finalTime);
                } else if (hasDandori) {
                    addSegment(lastPos, finalTime, 'bg-amber-400', 'Dandori');
                }
            }
        }
JS;
$c = str_replace($s2, $r2, $c);

file_put_contents($f, $c);
echo "Patched JS for Dandori\n";
