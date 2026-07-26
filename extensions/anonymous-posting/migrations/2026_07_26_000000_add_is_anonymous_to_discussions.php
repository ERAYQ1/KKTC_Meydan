<?php

use Flarum\Database\Migration;

return Migration::addColumns('discussions', [
    'is_anonymous' => ['boolean', 'default' => 0],
]);
