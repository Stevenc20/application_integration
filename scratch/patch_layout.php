<?php
$f = 'resources/views/operational/components/active-job-board.blade.php';
$c = file_get_contents($f);
$c = str_replace("\r\n", "\n", $c);

// 1. Fix the PLT button
$s1 = <<< 'BLADE'
                                        <button onclick="stepInput('active-actual-{{ $activeJob->id }}', {{ $activeJob->capacity ?? 0 }}, {{ $activeJob->id }})" class="flex-1 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-sm transition-all active:scale-95 shadow-sm shadow-emerald-200 flex items-center justify-center gap-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                                            PLT ({{ $activeJob->capacity ?? 0 }})
                                        </button>
BLADE;
$r1 = <<< 'BLADE'
                                        <button onclick="stepInput('active-actual-{{ $activeJob->id }}', {{ $activeJob->capacity ?? 0 }}, {{ $activeJob->id }})" class="flex-1 py-2 px-1 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs sm:text-sm transition-all active:scale-95 shadow-sm shadow-emerald-200 flex flex-col items-center justify-center gap-0.5">
                                            <span class="flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>PLT</span>
                                            <span class="text-[9px] opacity-90 leading-none">({{ $activeJob->capacity ?? 0 }})</span>
                                        </button>
BLADE;
$c = str_replace($s1, $r1, $c);

// 2. Fix the +1 button
$s2 = <<< 'BLADE'
                                        <button onclick="stepInput('active-actual-{{ $activeJob->id }}', 1, {{ $activeJob->id }})" class="flex-1 py-3 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 text-emerald-400 font-black text-sm transition-all active:scale-95 flex items-center justify-center gap-1">
BLADE;
$r2 = <<< 'BLADE'
                                        <button onclick="stepInput('active-actual-{{ $activeJob->id }}', 1, {{ $activeJob->id }})" class="flex-1 py-2 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 text-emerald-400 font-black text-xs sm:text-sm transition-all active:scale-95 flex items-center justify-center gap-1">
BLADE;
$c = str_replace($s2, $r2, $c);

// 3. Fix the -1 button
$s3 = <<< 'BLADE'
                                        <button onclick="stepInput('active-actual-{{ $activeJob->id }}', -1, {{ $activeJob->id }})" class="flex-1 py-3 rounded-xl bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 text-red-400 font-black text-sm transition-all active:scale-95 flex items-center justify-center gap-1">
BLADE;
$r3 = <<< 'BLADE'
                                        <button onclick="stepInput('active-actual-{{ $activeJob->id }}', -1, {{ $activeJob->id }})" class="flex-1 py-2 rounded-xl bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 text-red-400 font-black text-xs sm:text-sm transition-all active:scale-95 flex items-center justify-center gap-1">
BLADE;
$c = str_replace($s3, $r3, $c);

file_put_contents($f, $c);
echo "Patched Layout\n";
