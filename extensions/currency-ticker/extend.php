<?php

use Flarum\Extend;
use KktcMeydan\CurrencyTicker\Api\Controller\ListCurrencyRatesController;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Locales(__DIR__.'/locale')),

    (new Extend\Routes('api'))
        ->get('/currency-rates', 'kktcmeydan-currency-ticker.currency-rates.index', ListCurrencyRatesController::class),
];
