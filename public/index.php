<?php

$site = require __DIR__ . '/../site.php';

/*
|--------------------------------------------------------------------------
| Execute Flarum
|--------------------------------------------------------------------------
|
| Boot and run Flarum application HTTP server interface.
|
*/

$server = new Flarum\Http\Server($site);

$server->listen();
