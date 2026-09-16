<?php
$f = 'resources/js/operational/production-engine.js';
$c = file_get_contents($f);

// History Segment
$s1 = <<< 'JS'
            let color = 'bg-red-500';
            if (dt.type === 'dandori') color = 'bg-amber-400';
            else if (dt.type === 'firstcheck' || dt.type === '1st_check') color = 'bg-purple-500';
            else if (dt.type === 'try out') color = 'bg-orange-500';
            else if (dt.type === 'break time') color = 'bg-slate-500';
JS;
$r1 = <<< 'JS'
            let color = 'bg-red-500';
            if (dt.type === 'dandori') color = 'bg-amber-400';
            else if (dt.type === 'firstcheck' || dt.type === '1st_check') color = 'bg-purple-500';
            else if (dt.type === 'try out') color = 'bg-orange-500';
            else if (dt.type === 'break time') {
                color = dt.source === 'MANUAL' ? 'bg-slate-300' : 'bg-slate-500';
            }
JS;
$c = str_replace($s1, $r1, $c);

// Running segment
$s2 = <<< 'JS'
                else if (typeLower === 'try out' || typeLower === 'tryout') color = 'bg-orange-500';
                else if (typeLower === 'break time' || typeLower === 'break') { color = 'bg-slate-500'; extraClass = isLast ? 'active-growing' : ''; }
JS;
$r2 = <<< 'JS'
                else if (typeLower === 'try out' || typeLower === 'tryout') color = 'bg-orange-500';
                else if (typeLower === 'break time' || typeLower === 'break') {
                    color = rdItem.source === 'MANUAL' ? 'bg-slate-300' : 'bg-slate-500'; 
                    extraClass = isLast ? 'active-growing' : ''; 
                }
JS;
$c = str_replace($s2, $r2, $c);

// Sync from history (add source to rdItem)
$s3 = <<< 'JS'
                window.runningDowntimes[key] = {
                    id: entry.id,
                    start: new Date(entry.start),
                    jobId: aid,
                    btnType: bt,
                    dtType: entry.type
                };
JS;
$r3 = <<< 'JS'
                window.runningDowntimes[key] = {
                    id: entry.id,
                    start: new Date(entry.start),
                    jobId: aid,
                    btnType: bt,
                    dtType: entry.type,
                    source: entry.source
                };
JS;
$c = str_replace($s3, $r3, $c);

// serverBreakIsAuto (line 2807)
$s4 = <<< 'JS'
        const serverBreakIsAuto = !!(serverDown && serverDown.jenis_downtime === 'break time' && (serverDown.pic || '') === 'AUTO BREAK');
JS;
$r4 = <<< 'JS'
        const serverBreakIsAuto = !!(serverDown && serverDown.jenis_downtime === 'break time' && serverDown.source === 'AUTO');
JS;
$c = str_replace($s4, $r4, $c);

// set clientBreak.source when saving history on finish
$s5 = <<< 'JS'
                    type: clientBreak.dtType || 'break time',
                    id: clientBreak.id,
                    problem: clientBreak.problem || ''
JS;
$r5 = <<< 'JS'
                    type: clientBreak.dtType || 'break time',
                    id: clientBreak.id,
                    problem: clientBreak.problem || '',
                    source: clientBreak.source || ''
JS;
$c = str_replace($s5, $r5, $c);

// when server starts auto break
$s6 = <<< 'JS'
                btnType: 'break',
                dtType: 'break time',
                problem: serverBreakIsAuto ? 'AUTO BREAK' : (serverDown.problem || '')
            };
JS;
$r6 = <<< 'JS'
                btnType: 'break',
                dtType: 'break time',
                problem: serverBreakIsAuto ? 'AUTO BREAK' : (serverDown.problem || ''),
                source: serverDown.source || ''
            };
JS;
$c = str_replace($s6, $r6, $c);


file_put_contents($f, $c);
echo "Patched JS colors and source\n";
