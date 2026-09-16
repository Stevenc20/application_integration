<?php
function parsePartNames($inputStr) {
    // 1. Identify suffixes like FNS, WIP, BLK, GAL
    $suffixes = ['FNS', 'WIP', 'BLK', 'GAL', 'ASSY'];
    $foundSuffix = '';
    
    // Check if the string ends with any of the suffixes
    foreach ($suffixes as $s) {
        if (preg_match("/\s+{$s}$/i", $inputStr)) {
            $foundSuffix = " " . strtoupper($s);
            // Remove suffix for parsing
            $inputStr = preg_replace("/\s+{$s}$/i", '', $inputStr);
            break;
        }
    }

    if (!str_contains($inputStr, '/')) {
        return [$inputStr . $foundSuffix];
    }

    $parts = explode('/', $inputStr);
    $results = [];
    $base = trim($parts[0]);
    $results[] = $base . $foundSuffix;

    for ($i = 1; $i < count($parts); $i++) {
        $p = trim($parts[$i]);
        
        $len = strlen($p);
        $prefix = substr($base, 0, -$len);
        $results[] = $prefix . $p . $foundSuffix;
    }
    return $results;
}

print_r(parsePartNames("K-4047/48"));
print_r(parsePartNames("PVS-001B1/2B1"));
print_r(parsePartNames("GT-5154/5156 FNS"));
print_r(parsePartNames("SINGLE PART"));
print_r(parsePartNames("GT-123/125/127 WIP"));
