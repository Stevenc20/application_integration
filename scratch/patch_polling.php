<?php
$f = 'resources/js/operational/production-engine.js';
$c = file_get_contents($f);
$c = str_replace("\r\n", "\n", $c);

$s1 = <<< 'JS'
            updateTimeline();
            _updateBreakUI(id, serverBreakIsAuto ? 'BREAK TIME' : 'BREAK', true);
        } else if (!serverDown && clientBreak && window._autoBreakActive) {
JS;
$r1 = <<< 'JS'
            updateTimeline();
            if (serverBreakIsAuto) {
                _updateBreakUI(id, 'BREAK TIME', true);
            } else {
                _updateBreakUI(id, null, false);
            }
        } else if (!serverDown && clientBreak && clientBreak.dtType === 'break time') {
JS;
$c = str_replace($s1, $r1, $c);

$s2 = <<< 'JS'
                try { sessionStorage.removeItem('prod_break_state'); } catch (e) {}
            }
            window._autoBreakActive = false;
            window._autoBreakDowntimeId = null;
            window._autoBreakSkipped = false;
            window._autoBreakEndMin = null;
            updateTimeline();
            _updateBreakUI(id, null, false);
            showToast('Break time selesai, produksi dilanjutkan.', 'success');
        } else if (!serverDown && !clientBreak && window._autoBreakActive) {
JS;
$r2 = <<< 'JS'
                try { sessionStorage.removeItem('prod_break_state'); } catch (e) {}
            }
            const wasAutoBreak = window._autoBreakActive || (clientBreak.source === 'AUTO');
            window._autoBreakActive = false;
            window._autoBreakDowntimeId = null;
            window._autoBreakSkipped = false;
            window._autoBreakEndMin = null;
            updateTimeline();
            _updateBreakUI(id, null, false);
            if (wasAutoBreak) {
                showToast('Break time selesai, produksi dilanjutkan.', 'success');
            }
        } else if (!serverDown && !clientBreak && window._autoBreakActive) {
JS;
$c = str_replace($s2, $r2, $c);

file_put_contents($f, $c);
echo "Patched polling logic.\n";
