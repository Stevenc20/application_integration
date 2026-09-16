<?php
require __DIR__ . "/bootstrap/app.php";
$app = require __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$router = app("router");
$route = $router->getRoutes()->getByName("supervisor.handwork.api.store");
if ($route) {
    echo "Method: " . json_encode($route->methods()) . PHP_EOL;
    echo "URI: " . $route->uri() . PHP_EOL;
    echo "Action: " . $route->getActionName() . PHP_EOL;
    echo "Name: " . $route->getName() . PHP_EOL;
    echo "Middleware: " . json_encode($route->gatherMiddleware()) . PHP_EOL;
} else {
    echo "ROUTE NOT FOUND by name, trying URI match..." . PHP_EOL;
    foreach ($router->getRoutes() as $r) {
        if ($r->uri() === "supervisor/handwork/api/store") {
            echo "Found by URI:" . PHP_EOL;
            echo "Method: " . json_encode($r->methods()) . PHP_EOL;
            echo "URI: " . $r->uri() . PHP_EOL;
            echo "Action: " . $r->getActionName() . PHP_EOL;
            echo "Name: " . $r->getName() . PHP_EOL;
            echo "Middleware: " . json_encode($r->gatherMiddleware()) . PHP_EOL;
        }
    }
}

