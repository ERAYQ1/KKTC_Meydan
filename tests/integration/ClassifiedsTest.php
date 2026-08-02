<?php

namespace KktcMeydan\Tests\integration;

use Carbon\Carbon;
use Flarum\Testing\integration\TestCase;

class ClassifiedsTest extends TestCase
{
    const AUTHOR = 110;
    const DISCUSSION = 210;
    const POST = 310;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('kktcmeydan-classifieds');

        $now = Carbon::now()->toDateTimeString();

        $this->prepareDatabase([
            'users' => [
                [
                    'id' => self::AUTHOR,
                    'username' => 'ilan_sahibi',
                    'email' => 'ilan.sahibi@kktcmeydan.test',
                    'password' => 'hashedpassword',
                    'is_email_confirmed' => 1,
                    'joined_at' => $now,
                ],
            ],
            'discussions' => [
                [
                    'id' => self::DISCUSSION,
                    'title' => 'ILAN: Girne merkezde kiralik 1+1 daire',
                    'user_id' => self::AUTHOR,
                    'first_post_id' => self::POST,
                    'last_post_id' => self::POST,
                    'last_posted_user_id' => self::AUTHOR,
                    'comment_count' => 1,
                    'is_private' => 0,
                    'is_approved' => 1,
                    'created_at' => $now,
                    'last_posted_at' => $now,
                    'price' => 9000.50,
                    'currency' => 'TRY',
                    'location' => 'Girne',
                    'contact_phone' => '+90 542 555 00 00',
                    'classified_type' => 'kiralik',
                ],
            ],
            'posts' => [
                [
                    'id' => self::POST,
                    'discussion_id' => self::DISCUSSION,
                    'user_id' => self::AUTHOR,
                    'number' => 1,
                    'created_at' => $now,
                    'type' => 'comment',
                    'content' => '<t><p>Esyali, sahile 5 dakika.</p></t>',
                    'is_private' => 0,
                    'is_approved' => 1,
                ],
            ],
        ]);
    }

    /** @test */
    public function discussion_endpoint_exposes_classified_attributes()
    {
        $response = $this->send($this->request('GET', '/api/discussions/'.self::DISCUSSION));

        $this->assertEquals(200, $response->getStatusCode());

        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];

        $this->assertIsFloat($attributes['price']);
        $this->assertEquals(9000.50, $attributes['price']);
        $this->assertEquals('TRY', $attributes['currency']);
        $this->assertEquals('Girne', $attributes['location']);
        $this->assertEquals('+90 542 555 00 00', $attributes['contactPhone']);
        $this->assertEquals('kiralik', $attributes['classifiedType']);
    }

    /** @test */
    public function forum_endpoint_exposes_currency_and_type_options()
    {
        $response = $this->send($this->request('GET', '/api'));

        $this->assertEquals(200, $response->getStatusCode());

        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];

        $this->assertIsArray($attributes['classifiedCurrencies']);
        $this->assertContains('TRY', $attributes['classifiedCurrencies']);
        $this->assertContains('GBP', $attributes['classifiedCurrencies']);

        $this->assertIsArray($attributes['classifiedTypes']);
        $this->assertContains('satilik', $attributes['classifiedTypes']);
        $this->assertContains('kiralik', $attributes['classifiedTypes']);
    }
}
