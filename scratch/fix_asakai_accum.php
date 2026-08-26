<?php
$file = 'app/Http/Controllers/Supervisor/ReportController.php';
$content = file_get_contents($file);

// Replace accum and plan logic inside processShiftData
// Search for: 'accum' => 0,
// We will replace it with a TODO note or just leave it for the user to edit manually,
// But wait, the user says "semua kolom ini kita buat bisa di edit... kecuali repair line dan itemnya"
// If it is editable, the user can type the accum.
// But they are asking "accum ini udah cut off perbulan ga...?" which means they EXPECT the system to do it!
