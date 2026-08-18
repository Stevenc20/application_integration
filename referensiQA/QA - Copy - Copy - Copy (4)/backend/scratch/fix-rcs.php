<?php
$ic = \App\Models\ItemCheck::find(3);
if ($ic && $ic->status === 'revision') {
    $ic->status = 'waiting_qc_approval';
    $ic->catatan_revisi = null;
    $ic->field_revisions = null;
    $ic->save();
    dump('Fixed RCS-011');
} else {
    dump('Status is not revision or not found');
}
