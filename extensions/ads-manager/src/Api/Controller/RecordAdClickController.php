<?php

namespace KktcMeydan\AdsManager\Api\Controller;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Arr;
use KktcMeydan\AdsManager\Ad;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RecordAdClickController implements RequestHandlerInterface
{
    // Same IP re-clicking the same ad within this window is treated as a
    // repeat/bot event and skips the DB increment entirely, so a refresh
    // loop or click-bot script can't inflate clicks_count.
    private const THROTTLE_SECONDS = 60;

    public function __construct(private Cache $cache)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int) Arr::get($request->getQueryParams(), 'id');
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';

        $key = "ads_click_{$ip}_{$id}";

        if ($this->cache->has($key)) {
            return new EmptyResponse(204);
        }

        $this->cache->put($key, true, self::THROTTLE_SECONDS);

        Ad::findOrFail($id)->increment('clicks_count');

        return new EmptyResponse(204);
    }
}
