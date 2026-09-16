<?php
try {
    $call = App\Models\IntercomCall::whereIn('status', ['calling_gl', 'answered', 'arrived'])
        ->whereHas('lembarInspeksi', function ($q) {
            $q->where('assigned_gl_id', 2);
        })
        ->with('lembarInspeksi')
        ->latest('called_at')
        ->first();
    dump("Success");
} catch (\Exception $e) {
    dump("Error: " . $e->getMessage());
}
