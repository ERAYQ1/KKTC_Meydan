<?php

namespace KktcMeydan\CurrencyTicker\Api\Controller;

use Carbon\Carbon;
use Flarum\Settings\SettingsRepositoryInterface;
use KktcMeydan\CurrencyTicker\CurrencyRateFetcher;
use KktcMeydan\CurrencyTicker\FallbackCurrencyRates;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ListCurrencyRatesController implements RequestHandlerInterface
{
    const CACHE_KEY = 'kktcmeydan-currency-ticker.cache';
    const CACHE_TTL_SECONDS = 3600;

    /**
     * @var SettingsRepositoryInterface
     */
    private $settings;

    /**
     * @var CurrencyRateFetcher
     */
    private $fetcher;

    public function __construct(SettingsRepositoryInterface $settings, CurrencyRateFetcher $fetcher)
    {
        $this->settings = $settings;
        $this->fetcher = $fetcher;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse($this->getData());
    }

    private function getData(): array
    {
        $cached = $this->readCache();

        if ($cached !== null) {
            return $cached;
        }

        $data = $this->buildData();

        $this->settings->set(self::CACHE_KEY, json_encode([
            'generatedAt' => Carbon::now()->timestamp,
            'data' => $data,
        ]));

        return $data;
    }

    private function readCache(): ?array
    {
        $raw = $this->settings->get(self::CACHE_KEY);

        if (! $raw) {
            return null;
        }

        $decoded = json_decode($raw, true);

        if (
            ! is_array($decoded)
            || ! isset($decoded['generatedAt'], $decoded['data'])
            || Carbon::now()->timestamp - $decoded['generatedAt'] >= self::CACHE_TTL_SECONDS
        ) {
            return null;
        }

        return $decoded['data'];
    }

    private function buildData(): array
    {
        $rates = $this->fetcher->fetch();
        $source = $rates !== null ? 'open-er-api' : 'fallback';

        if ($rates === null) {
            $rates = FallbackCurrencyRates::rates();
        }

        return [
            'source' => $source,
            'generatedAt' => Carbon::now()->toIso8601String(),
            'rates' => [
                'GBP' => round($rates['GBP'], 2),
                'EUR' => round($rates['EUR'], 2),
                'USD' => round($rates['USD'], 2),
            ],
        ];
    }
}
