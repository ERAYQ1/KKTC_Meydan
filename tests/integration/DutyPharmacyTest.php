<?php

namespace KktcMeydan\Tests\integration;

use Flarum\Testing\integration\TestCase;

class DutyPharmacyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('kktcmeydan-duty-pharmacy');
    }

    /** @test */
    public function endpoint_returns_200_with_expected_json_structure()
    {
        $response = $this->send($this->request('GET', '/api/duty-pharmacies'));

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($body);
        $this->assertContains($body['source'], ['kteb', 'fallback']);
        $this->assertArrayHasKey('generatedAt', $body);
        $this->assertArrayHasKey('districts', $body);
        $this->assertArrayHasKey('emergencyNumbers', $body);

        $this->assertCount(6, $body['districts']);

        $expectedSlugs = ['lefkosa', 'girne', 'gazimagusa', 'guzelyurt', 'iskele', 'lefke'];
        $actualSlugs = array_column($body['districts'], 'slug');

        foreach ($expectedSlugs as $slug) {
            $this->assertContains($slug, $actualSlugs);
        }

        foreach ($body['districts'] as $district) {
            $this->assertArrayHasKey('name', $district);
            $this->assertArrayHasKey('pharmacies', $district);
            $this->assertIsArray($district['pharmacies']);
        }

        $this->assertNotEmpty($body['emergencyNumbers']);

        foreach ($body['emergencyNumbers'] as $entry) {
            $this->assertArrayHasKey('label', $entry);
            $this->assertArrayHasKey('number', $entry);
            $this->assertArrayHasKey('phone', $entry);
            $this->assertStringStartsWith('tel:', $entry['phone']);
        }
    }

    /** @test */
    public function second_request_is_served_from_cache()
    {
        $first = json_decode((string) $this->send($this->request('GET', '/api/duty-pharmacies'))->getBody(), true);
        $second = json_decode((string) $this->send($this->request('GET', '/api/duty-pharmacies'))->getBody(), true);

        $this->assertEquals($first['generatedAt'], $second['generatedAt']);
    }
}
