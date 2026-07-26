<?php

use Flarum\Database\Migration;

return Migration::addColumns('posts', [
    'is_anonymous' => ['boolean', 'default' => 0],
]);
