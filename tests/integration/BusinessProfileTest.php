<?php

namespace KktcMeydan\Tests\integration;

use Carbon\Carbon;
use Flarum\Testing\integration\TestCase;

class BusinessProfileTest extends TestCase
{
    /**
     * seed.php'de "İşletme" rolu 6. grup olarak olusuyor; BusinessGroupGate
     * grubu ID ile degil `name_singular` ile esliyor, fikstur de bu yuzden
     * ismi birebir kuruyor.
     */
    const BUSINESS_GROUP = 6;

    const BUSINESS_USER = 115;
    const REVIEWER = 116;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('kktcmeydan-business-profile');

        $now = Carbon::now()->toDateTimeString();

        $preferences = json_encode([
            'business_address' => 'Sakarya, Gazimagusa',
            'business_phone' => '+90 392 555 00 00',
            'business_whatsapp' => '+90 542 555 00 00',
            'business_hours' => 'Her gun 08:00 - 22:00',
        ]);

        $this->prepareDatabase([
            'groups' => [
                [
                    'id' => self::BUSINESS_GROUP,
                    'name_singular' => 'İşletme',
                    'name_plural' => 'İşletmeler',
                    'color' => '#f59e0b',
                    'icon' => 'fas fa-store',
                    'is_hidden' => 0,
                ],
            ],
            'users' => [
                [
                    'id' => self::BUSINESS_USER,
                    'username' => 'liman_kafe',
                    'email' => 'liman.kafe@kktcmeydan.test',
                    'password' => 'hashedpassword',
                    'is_email_confirmed' => 1,
                    'joined_at' => $now,
                    'preferences' => $preferences,
                ],
                [
                    'id' => self::REVIEWER,
                    'username' => 'degerlendiren',
                    'email' => 'degerlendiren@kktcmeydan.test',
                    'password' => 'hashedpassword',
                    'is_email_confirmed' => 1,
                    'joined_at' => $now,
                ],
            ],
            'group_user' => [
                ['user_id' => self::BUSINESS_USER, 'group_id' => self::BUSINESS_GROUP],
            ],
            'business_reviews' => [
                [
                    'business_user_id' => self::BUSINESS_USER,
                    'reviewer_user_id' => self::REVIEWER,
                    'rating' => 5,
                    'comment' => 'Kahvesi cok iyi.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
        ]);
    }

    /** @test */
    public function user_endpoint_exposes_business_attributes_for_business_group_members()
    {
        $response = $this->send($this->request('GET', '/api/users/'.self::BUSINESS_USER));

        $this->assertEquals(200, $response->getStatusCode());

        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];

        $this->assertTrue($attributes['isBusinessUser']);
        $this->assertEquals('Sakarya, Gazimagusa', $attributes['businessAddress']);
        $this->assertEquals('+90 392 555 00 00', $attributes['businessPhone']);
        $this->assertEquals('+90 542 555 00 00', $attributes['businessWhatsapp']);
    }

    /** @test */
    public function non_business_users_do_not_leak_contact_fields()
    {
        $response = $this->send($this->request('GET', '/api/users/'.self::REVIEWER));

        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];

        $this->assertFalse($attributes['isBusinessUser']);
        $this->assertNull($attributes['businessAddress']);
        $this->assertNull($attributes['businessPhone']);
    }

    /** @test */
    public function business_reviews_endpoint_returns_reviews_for_the_business()
    {
        // Kontrolcu isletmeyi `filter[business]` ile okuyor (bkz.
        // ListBusinessReviewsController), rotanin adi degil bu parametre.
        $response = $this->send(
            $this->request('GET', '/api/business-reviews')
                ->withQueryParams(['filter' => ['business' => self::BUSINESS_USER]])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($body['data']);
        $this->assertCount(1, $body['data']);
        $this->assertEquals(5, $body['data'][0]['attributes']['rating']);
    }
}
