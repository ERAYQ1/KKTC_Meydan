<?php

use Flarum\Extend;
use KktcMeydan\AdsManager\AdRequestThrottler;
use KktcMeydan\AdsManager\Api\Controller\CreateAdController;
use KktcMeydan\AdsManager\Api\Controller\DeleteAdController;
use KktcMeydan\AdsManager\Api\Controller\ListAdsController;
use KktcMeydan\AdsManager\Api\Controller\RecordAdClickController;
use KktcMeydan\AdsManager\Api\Controller\RecordAdImpressionController;
use KktcMeydan\AdsManager\Api\Controller\UpdateAdController;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    (new Extend\Locales(__DIR__.'/locale')),

    (new Extend\ThrottleApi())
        ->set('kktcmeydanAdsManagerWindow', AdRequestThrottler::class),

    (new Extend\Routes('api'))
        ->get('/ads', 'kktcmeydan-ads-manager.ads.index', ListAdsController::class)
        ->post('/ads', 'kktcmeydan-ads-manager.ads.create', CreateAdController::class)
        ->patch('/ads/{id}', 'kktcmeydan-ads-manager.ads.update', UpdateAdController::class)
        ->delete('/ads/{id}', 'kktcmeydan-ads-manager.ads.delete', DeleteAdController::class)
        ->post('/ads/{id}/impression', 'kktcmeydan-ads-manager.ads.impression', RecordAdImpressionController::class)
        ->post('/ads/{id}/click', 'kktcmeydan-ads-manager.ads.click', RecordAdClickController::class),
];
