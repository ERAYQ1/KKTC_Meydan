<?php

use Flarum\Database\Migration;

return Migration::addColumns('discussions', [
    'report_status' => ['string', 'length' => 32, 'nullable' => true, 'default' => null],
]);
