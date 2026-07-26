<?php

use Flarum\Database\Migration;

return Migration::addColumns('discussions', [
    'event_start_at' => ['dateTime', 'nullable' => true, 'default' => null],
    'event_end_at' => ['dateTime', 'nullable' => true, 'default' => null],
]);
