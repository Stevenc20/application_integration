<?php
try {
    $call = App\Models\IntercomCall::with('lembarInspeksi')->first();
    if ($call) {
        $arr = $call->toArray();
    }
    dump("Success");
} catch (\Throwable $e) {
    dump("Error: " . $e->getMessage());
}
