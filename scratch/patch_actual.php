<?php
$f = 'resources/js/operational/production-engine.js';
$c = file_get_contents($f);

// Find the block updating timeline-time-label
$s = <<< 'JS'
                const label = document.getElementById('timeline-time-label');
                if (label) {
                    const displayProgress = Math.round(realPct);
                    if (realPct > 100) {
                        label.innerHTML = `<span class="text-red-500 animate-pulse">OVER ${displayProgress}%</span>`;
                        label.classList.remove('text-blue-500');
                        label.classList.add('text-red-500', 'animate-pulse');
                        if (!document.getElementById('overtime-badge')) {
                            const badge = document.createElement('div');
                            badge.id = 'overtime-badge';
                            badge.className = 'text-[8px] font-black text-red-500 uppercase tracking-widest mt-1 animate-bounce';
                            badge.innerText = 'OVERTIME PROCESS';
                            label.parentElement.appendChild(badge);
                        }
                    } else {
                        label.innerText = displayProgress + '%';
                        label.classList.remove('text-red-500', 'animate-pulse');
                        label.classList.add('text-blue-600');
                        const badge = document.getElementById('overtime-badge');
                        if (badge) badge.remove();
                    }
                }
JS;

$r = <<< 'JS'
                const label = document.getElementById('timeline-time-label');
                if (label) {
                    const displayProgress = achievementPct;
                    if (achievementPct > 100) {
                        label.innerHTML = `<span class="text-red-500 animate-pulse">OVER ${displayProgress}%</span>`;
                        label.classList.remove('text-blue-600');
                        label.classList.add('text-red-500', 'animate-pulse');
                        if (!document.getElementById('overtarget-badge')) {
                            const badge = document.createElement('div');
                            badge.id = 'overtarget-badge';
                            badge.className = 'text-[8px] font-black text-red-500 uppercase tracking-widest mt-1 animate-bounce';
                            badge.innerText = 'OVER TARGET';
                            label.parentElement.appendChild(badge);
                        }
                    } else {
                        label.innerText = displayProgress + '%';
                        label.classList.remove('text-red-500', 'animate-pulse');
                        label.classList.add('text-blue-600');
                        const badge = document.getElementById('overtarget-badge');
                        if (badge) badge.remove();
                    }
                }
JS;

$c = str_replace(str_replace("\r\n", "\n", $s), str_replace("\r\n", "\n", $r), str_replace("\r\n", "\n", $c));

file_put_contents($f, $c);
echo "Patched timeline label.\n";
