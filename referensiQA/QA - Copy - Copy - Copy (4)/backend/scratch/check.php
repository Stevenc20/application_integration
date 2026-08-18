<?php
$docs = App\Models\ItemCheck::whereNotNull('waktu_mulai')->get();
foreach($docs as $d) echo $d->id . " - status: " . $d->status . " - start: " . $d->waktu_mulai . " - end: " . $d->waktu_selesai . "\n";
