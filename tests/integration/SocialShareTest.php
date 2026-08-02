<?php

namespace KktcMeydan\Tests\integration;

use Flarum\Testing\integration\TestCase;

/**
 * Eklenti tamamen on yuz (JS/LESS) - test edilebilir tek sunucu davranisi,
 * varliklarinin forum onyuzunu bozmadan yuklenmesi. Bozuk bir extend.php ya
 * da eksik dist dosyasi bu istegi 500'e dusururdu.
 */
class SocialShareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('kktcmeydan-social-share');
    }

    /** @test */
    public function forum_frontend_boots_with_the_extension_enabled()
    {
        $response = $this->send($this->request('GET', '/'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('<!doctype html>', strtolower((string) $response->getBody()));
    }
}
