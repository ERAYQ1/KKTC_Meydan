<?php

namespace KktcMeydan\Tests\integration;

use Flarum\Testing\integration\TestCase;

class CurrencyTickerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('kktcmeydan-currency-ticker');
    }

    /** @test */
    public function endpoint_returns_200_with_expected_json_structure()
    {
        $response = $this->send($this->request('GET', '/api/currency-rates'));

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($body);
        $this->assertContains($body['source'], ['open-er-api', 'fallback']);
        $this->assertArrayHasKey('generatedAt', $body);
        $this->assertArrayHasKey('rates', $body);

        foreach (['GBP', 'EUR', 'USD'] as $currency) {
            $this->assertArrayHasKey($currency, $body['rates']);
            $this->assertIsFloat($body['rates'][$currency]);
            $this->assertGreaterThan(0, $body['rates'][$currency]);
        }
    }

    /** @test */
    public function second_request_is_served_from_cache()
    {
        $first = json_decode((string) $this->send($this->request('GET', '/api/currency-rates'))->getBody(), true);
        $second = json_decode((string) $this->send($this->request('GET', '/api/currency-rates'))->getBody(), true);

        $this->assertEquals($first['generatedAt'], $second['generatedAt']);
    }
}
