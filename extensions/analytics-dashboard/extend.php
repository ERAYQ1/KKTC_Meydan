<?php

use Flarum\Extend;
use KktcMeydan\AnalyticsDashboard\Api\Controller\ShowAnalyticsSummaryController;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    (new Extend\Locales(__DIR__.'/locale')),

    (new Extend\Routes('api'))
        ->get('/analytics/summary', 'kktcmeydan-analytics-dashboard.summary', ShowAnalyticsSummaryController::class),
];
