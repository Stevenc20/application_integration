<?php
/**
 * DEBUG RUNDOWN PRESS v2
 * Taruh di: public/debug_rundown.php (timpa yang lama)
 * Akses: http://127.0.0.1:8000/debug_rundown.php
 */

$basePath = dirname(__DIR__);

echo "<style>body{font-family:monospace;padding:20px;} pre{background:#f4f4f4;padding:10px;border-radius:4px;overflow-x:auto;} .ok{color:green;font-weight:bold;} .err{color:red;font-weight:bold;} .warn{color:orange;font-weight:bold;}</style>";
echo "<h2>Debug Rundown Press v2</h2><pre>";

// 1. Python
echo "=== 1. PYTHON ===\n";
$python = null;
foreach (['python', 'python3', 'py'] as $cmd) {
    $out = shell_exec("{$cmd} --version 2>&1");
    $found = $out && str_contains($out, 'Python 3');
    echo "{$cmd}: " . ($out ? trim($out) : 'NOT FOUND') . ($found ? " <span class='ok'>✓</span>" : "") . "\n";
    if ($found && !$python) $python = $cmd;
}
echo "Python to use: " . ($python ?? '<span class=\'err\'>NONE FOUND</span>') . "\n";

// 2. Script
echo "\n=== 2. SCRIPT ===\n";
$scriptPath = $basePath . '/read_rundown_press.py';
echo "Path: {$scriptPath}\n";
echo "Exists: " . (file_exists($scriptPath) ? "<span class='ok'>YES</span>" : "<span class='err'>NO</span>") . "\n";

// 3. Storage
echo "\n=== 3. STORAGE ===\n";
$uploadDir = $basePath . '/storage/app/uploads';
echo "Dir: {$uploadDir}\n";
echo "Exists: " . (is_dir($uploadDir) ? "<span class='ok'>YES</span>" : "<span class='err'>NO</span>") . "\n";
echo "Writable: " . (is_writable($uploadDir) ? "<span class='ok'>YES</span>" : "<span class='err'>NO</span>") . "\n";

// 4. Database
echo "\n=== 4. DATABASE ===\n";
try {
    $dbPath = $basePath . '/database/database.sqlite';
    $pdo = new PDO("sqlite:{$dbPath}");
    $count = $pdo->query("SELECT COUNT(*) FROM rundown_presses")->fetchColumn();
    echo "rundown_presses rows: {$count}\n";
    if ($count > 0) {
        $rows = $pdo->query("SELECT sheet_date, COUNT(*) as c FROM rundown_presses GROUP BY sheet_date ORDER BY sheet_date")->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as $r) echo "  {$r['sheet_date']}: {$r['c']} rows\n";
    }
} catch(Exception $e) { echo "<span class='err'>DB Error: {$e->getMessage()}</span>\n"; }

echo "</pre><hr>";

// 5. Upload test form
echo "<h3>Test Upload Langsung</h3>";
echo "<form method='POST' enctype='multipart/form-data'><input type='file' name='test_excel' accept='.xlsm,.xlsx,.xls'> <button type='submit'>Upload & Parse</button></form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_excel'])) {
    echo "<pre>";
    
    $uploadedFile = $_FILES['test_excel'];
    $name = $uploadedFile['name'];
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $size = $uploadedFile['size'];
    $tmp  = $uploadedFile['tmp_name'];
    $err  = $uploadedFile['error'];
    
    echo "=== UPLOAD INFO ===\n";
    echo "Name     : {$name}\n";
    echo "Extension: {$ext}\n";
    echo "Size     : " . number_format($size) . " bytes\n";
    echo "Tmp path : {$tmp}\n";
    echo "PHP error: {$err} " . ($err === 0 ? "(OK)" : "<span class='err'>(ERROR!)</span>") . "\n";
    echo "Tmp exists: " . (file_exists($tmp) ? "YES" : "<span class='err'>NO</span>") . "\n";
    
    if ($err !== 0) {
        $phpErrors = [1=>'File too large (php.ini)',2=>'File too large (form)',3=>'Partial upload',4=>'No file',6=>'No tmp dir',7=>'Cannot write',8=>'Extension stopped'];
        echo "<span class='err'>Upload error: " . ($phpErrors[$err] ?? "Unknown {$err}") . "</span>\n";
    } else {
        // Save file
        $dest = $uploadDir . '/debug_upload.' . $ext;
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        if (move_uploaded_file($tmp, $dest)) {
            echo "Saved to  : {$dest}\n";
            echo "File size : " . filesize($dest) . " bytes\n";
            
            // Run python script
            echo "\n=== PYTHON EXECUTION ===\n";
            if (!$python) {
                echo "<span class='err'>No Python found!</span>\n";
            } else {
                $cmd = escapeshellcmd($python) 
                     . ' ' . escapeshellarg($scriptPath)
                     . ' ' . escapeshellarg($dest)
                     . ' ' . escapeshellarg($name)
                     . ' 2>&1';
                echo "CMD: {$cmd}\n\n";
                
                $output = shell_exec($cmd);
                echo "RAW OUTPUT (" . strlen($output ?? '') . " chars):\n";
                echo htmlspecialchars(substr($output ?? 'NULL - shell_exec returned null', 0, 3000)) . "\n";
                
                // Try to parse JSON
                $jsonStart = strpos($output ?? '', '{');
                if ($jsonStart !== false) {
                    $data = json_decode(substr($output, $jsonStart), true);
                    if ($data) {
                        echo "\n=== PARSED RESULT ===\n";
                        if (isset($data['error'])) {
                            echo "<span class='err'>ERROR: {$data['error']}</span>\n";
                            if (isset($data['trace'])) echo "TRACE:\n" . htmlspecialchars($data['trace']) . "\n";
                        } elseif (isset($data['sheets'])) {
                            echo "<span class='ok'>SUCCESS!</span>\n";
                            echo "Total dates: " . count($data['sheets']) . "\n";
                            foreach($data['sheets'] as $date => $rows) {
                                echo "  {$date}: " . count($rows) . " rows";
                                if (count($rows) > 0) echo " | first job: " . $rows[0]['job_no'];
                                echo "\n";
                            }
                            
                            // Now test INSERT into DB
                            echo "\n=== TEST INSERT DATABASE ===\n";
                            try {
                                $pdo->exec("BEGIN");
                                $inserted = 0;
                                $firstDate = null;
                                foreach($data['sheets'] as $date => $rows) {
                                    if (!$firstDate) $firstDate = $date;
                                    $pdo->exec("DELETE FROM rundown_presses WHERE sheet_date = " . $pdo->quote($date));
                                    $stmt = $pdo->prepare("INSERT INTO rundown_presses (sheet_date,no,job_no,tipe,vendor,update_stock,stock_awal,price,incoming,iami,spare_part,gkd,sap,kap,gmo,stok_akhir,pcs_day,strength,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,datetime('now'),datetime('now'))");
                                    foreach($rows as $item) {
                                        $stmt->execute([
                                            $date,
                                            $item['no'] ?? null,
                                            $item['job_no'] ?? null,
                                            $item['tipe'] ?? null,
                                            $item['vendor'] ?? null,
                                            $item['update_stock'] ?? null,
                                            $item['stock_awal'] ?? 0,
                                            $item['price'] ?? 0,
                                            $item['incoming'] ?? 0,
                                            $item['iami'] ?? 0,
                                            $item['spare_part'] ?? 0,
                                            $item['gkd'] ?? 0,
                                            $item['sap'] ?? 0,
                                            $item['kap'] ?? 0,
                                            $item['gmo'] ?? 0,
                                            $item['stok_akhir'] ?? 0,
                                            $item['pcs_day'] ?? 0,
                                            $item['strength'] ?? 0,
                                            $item['status'] ?? 'STANDAR',
                                        ]);
                                        $inserted++;
                                    }
                                }
                                $pdo->exec("COMMIT");
                                echo "<span class='ok'>INSERT OK! {$inserted} rows masuk ke database.</span>\n";
                                echo "Sekarang buka: <a href='/rundown-press?sheet=" . urlencode($firstDate) . "' target='_blank'>/rundown-press?sheet={$firstDate}</a>\n";
                                
                                // Verify
                                $verifyCount = $pdo->query("SELECT COUNT(*) FROM rundown_presses")->fetchColumn();
                                echo "Total rows di DB sekarang: {$verifyCount}\n";
                            } catch(Exception $e) {
                                $pdo->exec("ROLLBACK");
                                echo "<span class='err'>INSERT ERROR: {$e->getMessage()}</span>\n";
                            }
                        }
                    } else {
                        echo "<span class='err'>JSON parse failed!</span>\n";
                    }
                } else {
                    echo "<span class='err'>No JSON in output!</span>\n";
                }
            }
            @unlink($dest);
        } else {
            echo "<span class='err'>move_uploaded_file() FAILED!</span>\n";
            echo "Check permissions on: {$uploadDir}\n";
        }
    }
    echo "</pre>";
}
