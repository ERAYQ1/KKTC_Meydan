<?php

namespace KktcMeydan\Tests\integration;

use Carbon\Carbon;
use Flarum\Testing\integration\TestCase;

class AdsManagerTest extends TestCase
{
    const ACTIVE_AD = 500;
    const INACTIVE_AD = 501;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('kktcmeydan-ads-manager');

        $now = Carbon::now()->toDateTimeString();

        $this->prepareDatabase([
            'ads' => [
                [
                    'id' => self::ACTIVE_AD,
                    'title' => 'Ercan Havalimani Ulasim Rehberi',
                    'image_url' => 'https://kktcmeydan.test/assets/ads/ercan.svg',
                    'target_url' => 'https://kktcmeydan.test/t/ulasim',
                    'target_category_slug' => 'ulasim',
                    'is_active' => 1,
                    'impressions_count' => 0,
                    'clicks_count' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => self::INACTIVE_AD,
                    'title' => 'Pasif reklam',
                    'image_url' => 'https://kktcmeydan.test/assets/ads/pasif.svg',
                    'target_url' => 'https://kktcmeydan.test/t/ulasim',
                    'target_category_slug' => 'ulasim',
                    'is_active' => 0,
                    'impressions_count' => 0,
                    'clicks_count' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
        ]);
    }

    /** @test */
    public function ads_endpoint_returns_active_ads_targeting_the_tag()
    {
        // `TestCase::request()` URI'deki sorgu dizesini ayristirmiyor
        // (ServerRequest bos $_GET ile kuruluyor), parametreler acikca
        // verilmeli - aksi halde kontrolcu "filtresiz" dalina duser.
        $response = $this->send(
            $this->request('GET', '/api/ads')->withQueryParams(['filter' => ['tag' => 'ulasim']])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        $ids = array_column($body['data'], 'id');

        $this->assertContains((string) self::ACTIVE_AD, $ids);
        $this->assertNotContains((string) self::INACTIVE_AD, $ids);
    }

    /** @test */
    public function impression_endpoint_returns_204_and_increments_the_counter()
    {
        // Rota misafire acik ama POST oldugu icin CSRF korumasina tabi;
        // token olmadan istek 400 ile doner.
        $response = $this->send($this->requestWithCsrfToken(
            $this->request('POST', '/api/ads/'.self::ACTIVE_AD.'/impression')
        ));

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEmpty((string) $response->getBody());

        $this->assertEquals(1, $this->database()->table('ads')->where('id', self::ACTIVE_AD)->value('impressions_count'));
    }
}
