<?php
$routesFile = 'routes/web.php';
$content = file_get_contents($routesFile);
$route = <<< 'PHP'
Route::get('/run-migration-now', function () {
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    return \Illuminate\Support\Facades\Artisan::output();
});
PHP;
if (!str_contains($content, '/run-migration-now')) {
    file_put_contents($routesFile, $content . "\n" . $route);
}
