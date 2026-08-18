$f = 'resources\views\dashboard.blade.php'
$a = Get-Content $f -Encoding UTF8
$r = $a[0..307] + $a[483..($a.Count-1)]
Set-Content $f -Value $r -Encoding UTF8
Write-Host "Done. Total lines: $($r.Count)"
