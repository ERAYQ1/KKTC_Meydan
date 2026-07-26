<?php

use Flarum\Database\Migration;

return Migration::addColumns('discussions', [
    'price' => ['decimal', 'total' => 12, 'places' => 2, 'nullable' => true, 'default' => null],
    'currency' => ['string', 'length' => 8, 'default' => 'TRY'],
    'location' => ['string', 'length' => 255, 'nullable' => true, 'default' => null],
    'contact_phone' => ['string', 'length' => 32, 'nullable' => true, 'default' => null],
    'classified_type' => ['string', 'length' => 32, 'nullable' => true, 'default' => null],
]);
