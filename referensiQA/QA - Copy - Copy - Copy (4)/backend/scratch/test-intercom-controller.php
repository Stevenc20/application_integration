<?php
try {
    $req = new Illuminate\Http\Request();
    $req->merge(['user_id' => 2, 'role' => 'group leader']);
    $controller = new App\Http\Controllers\Api\IntercomController();
    $res = $controller->checkActiveIncoming($req);
    dump("Success");
    dump($res->getContent());
} catch (\Throwable $e) {
    dump("Error: " . $e->getMessage());
    dump($e->getTraceAsString());
}
