<?php
$f = 'resources/views/operational/components/active-job-board.blade.php';
$c = file_get_contents($f);
$c = str_replace("\r\n", "\n", $c);

// Repair button
$s1 = <<< 'BLADE'
                                    <button onclick="stepInput('active-repair-{{ $activeJob->id }}', 1, {{ $activeJob->id }})" class="w-full py-3 rounded-xl bg-orange-500/10 border border-orange-500/20 text-orange-400 font-black text-sm hover:bg-orange-500 hover:text-white transition-all active:scale-95 flex items-center justify-center gap-1">
BLADE;
$r1 = <<< 'BLADE'
                                    <button onclick="stepInput('active-repair-{{ $activeJob->id }}', 1, {{ $activeJob->id }})" class="w-full py-2 rounded-xl bg-orange-500/10 border border-orange-500/20 text-orange-400 font-black text-xs sm:text-sm hover:bg-orange-500 hover:text-white transition-all active:scale-95 flex items-center justify-center gap-1">
BLADE;
$c = str_replace($s1, $r1, $c);

// Reject button
$s2 = <<< 'BLADE'
                                    <button onclick="stepInput('active-reject-{{ $activeJob->id }}', 1, {{ $activeJob->id }})" class="w-full py-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 font-black text-sm hover:bg-red-500 hover:text-white transition-all active:scale-95 flex items-center justify-center gap-1">
BLADE;
$r2 = <<< 'BLADE'
                                    <button onclick="stepInput('active-reject-{{ $activeJob->id }}', 1, {{ $activeJob->id }})" class="w-full py-2 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 font-black text-xs sm:text-sm hover:bg-red-500 hover:text-white transition-all active:scale-95 flex items-center justify-center gap-1">
BLADE;
$c = str_replace($s2, $r2, $c);

file_put_contents($f, $c);
echo "Patched Repair Reject Layout\n";
