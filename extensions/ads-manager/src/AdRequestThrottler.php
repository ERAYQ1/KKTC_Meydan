<?php

namespace KktcMeydan\AdsManager;

use Illuminate\Contracts\Cache\Repository as Cache;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Impression/click sayaclari kimlik dogrulamasiz, tek istekle tetiklenir -
 * korumasiz birakilirsa bir script ayni reklami saniyede binlerce kez
 * "tiklatip" sayaci ve olasi tiklama-basi-odeme raporlamasini sisirebilir.
 *
 * IP+reklam basina bir pencere boyunca ikinci istegi 429'a (FloodingException)
 * dusurur. Flarum'un varsayilan throttler'lari kayitli kullaniciyi hedefler;
 * bu iki rota misafir erisimine acik oldugundan ayri bir throttler gerekiyor.
 */
class AdRequestThrottler
{
    private const WINDOW_SECONDS = [
        'kktcmeydan-ads-manager.ads.impression' => 5,
        'kktcmeydan-ads-manager.ads.click' => 30,
    ];

    public function __construct(private Cache $cache)
    {
    }

    public function __invoke(ServerRequestInterface $request)
    {
        $route = $request->getAttribute('routeName');

        if (! isset(self::WINDOW_SECONDS[$route])) {
            return;
        }

        // Route placeholders aren't merged into getQueryParams() until the
        // route's controller closure runs (Flarum\Http\RouteHandlerFactory)
        // - the terminal handler invoked AFTER this middleware. They're
        // available earlier via the `routeParameters` attribute that
        // ResolveRoute sets, which is what must be read here.
        $routeParameters = $request->getAttribute('routeParameters') ?? [];
        $adId = (int) ($routeParameters['id'] ?? 0);
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';

        $key = "kktcmeydan-ads-manager.throttle.$route.$ip.$adId";

        if ($this->cache->has($key)) {
            return true;
        }

        $this->cache->put($key, true, self::WINDOW_SECONDS[$route]);
    }
}
