<?php

use Flarum\Extend;
use KktcMeydan\DutyPharmacy\Api\Controller\ListDutyPharmaciesController;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Locales(__DIR__.'/locale')),

    (new Extend\Routes('api'))
        ->get('/duty-pharmacies', 'kktcmeydan-duty-pharmacy.duty-pharmacies.index', ListDutyPharmaciesController::class),
];
