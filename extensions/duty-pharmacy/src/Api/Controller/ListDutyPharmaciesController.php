<?php

namespace KktcMeydan\DutyPharmacy\Api\Controller;

use Carbon\Carbon;
use Flarum\Settings\SettingsRepositoryInterface;
use KktcMeydan\DutyPharmacy\DutyPharmacyScraper;
use KktcMeydan\DutyPharmacy\EmergencyNumbers;
use KktcMeydan\DutyPharmacy\FallbackDutyPharmacies;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ListDutyPharmaciesController implements RequestHandlerInterface
{
    const CACHE_KEY = 'kktcmeydan-duty-pharmacy.cache';
    const CACHE_TTL_SECONDS = 7200;

    /**
     * @var SettingsRepositoryInterface
     */
    private $settings;

    /**
     * @var DutyPharmacyScraper
     */
    private $scraper;

    public function __construct(SettingsRepositoryInterface $settings, DutyPharmacyScraper $scraper)
    {
        $this->settings = $settings;
        $this->scraper = $scraper;
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
        $scraped = $this->scraper->scrape();
        $source = $scraped !== null ? 'kteb' : 'fallback';

        $districts = [];

        foreach (DutyPharmacyScraper::DISTRICTS as $slug => $name) {
            $pharmacies = $scraped !== null
                ? $scraped[$slug]
                : FallbackDutyPharmacies::forDistrict($slug);

            $districts[] = [
                'slug' => $slug,
                'name' => $name,
                'pharmacies' => $pharmacies,
            ];
        }

        return [
            'source' => $source,
            'generatedAt' => Carbon::now()->toIso8601String(),
            'districts' => $districts,
            'emergencyNumbers' => EmergencyNumbers::all(),
        ];
    }
}
