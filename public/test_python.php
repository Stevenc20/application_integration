<?php
echo "Testing Python Execution...\n";

function findPython() {
    $candidates = ['python', 'python3', 'py'];
    $appData = getenv('LOCALAPPDATA') ?: getenv('APPDATA');
    $userProfile = getenv('USERPROFILE') ?: getenv('HOME');
    $windowsPaths = [];
    foreach (['Python312', 'Python311', 'Python310', 'Python39', 'Python38'] as $ver) {
        $windowsPaths[] = 'C:\\Python' . substr($ver, 6) . '\\python.exe';
        if ($appData) $windowsPaths[] = $appData . '\\Programs\\Python\\' . $ver . '\\python.exe';
        if ($userProfile) $windowsPaths[] = $userProfile . '\\AppData\\Local\\Programs\\Python\\' . $ver . '\\python.exe';
    }
    foreach ($windowsPaths as $path) {
        $cleanPath = str_replace('\\', '\\', $path);
        if (file_exists($cleanPath)) $candidates[] = $cleanPath;
    }
    
    echo "Checking candidates:\n";
    foreach ($candidates as $cmd) {
        $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = @proc_open([$cmd, '--version'], $desc, $pipes);
        if (!is_resource($process)) {
            echo "- $cmd: Failed proc_open\n";
            continue;
        }
        fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        $output = trim(($out ?? '') . ($err ?? ''));
        echo "- $cmd: $output\n";
        
        if (strpos($output, 'Python 3') !== false) {
            return $cmd;
        }
    }
    return null;
}

$python = findPython();
if (!$python) {
    echo "\nERROR: Python 3 not found!\n";
    exit(1);
}

echo "\nFOUND PYTHON: $python\n";

// Test openpyxl
echo "Testing openpyxl module...\n";
$desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$process = @proc_open([$python, '-c', 'import openpyxl; print("Openpyxl OK")'], $desc, $pipes);
if (is_resource($process)) {
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);
    echo trim(($out ?? '') . ($err ?? '')) . "\n";
} else {
    echo "Failed to test openpyxl\n";
}

echo "\nDone.\n";
