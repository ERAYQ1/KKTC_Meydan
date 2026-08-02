<?php

namespace KktcMeydan\Tests\integration;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Testing\integration\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    const ADMIN = 117;
    const MEMBER = 118;

    protected function setUp(): void
    {
        parent::setUp();

        // Panel populer etiketleri raporluyor; composer.json'da `flarum/tags`
        // sert bagimlilik, once o etkinlestirilmezse eklenti bootta
        // MissingDependenciesException atiyor.
        $this->extension('flarum-tags', 'kktcmeydan-analytics-dashboard');

        $now = Carbon::now()->toDateTimeString();

        $this->prepareDatabase([
            'users' => [
                [
                    'id' => self::ADMIN,
                    'username' => 'panel_admin',
                    'email' => 'panel.admin@kktcmeydan.test',
                    'password' => 'hashedpassword',
                    'is_email_confirmed' => 1,
                    'joined_at' => $now,
                ],
                [
                    'id' => self::MEMBER,
                    'username' => 'normal_uye',
                    'email' => 'normal.uye@kktcmeydan.test',
                    'password' => 'hashedpassword',
                    'is_email_confirmed' => 1,
                    'joined_at' => $now,
                ],
            ],
            'group_user' => [
                ['user_id' => self::ADMIN, 'group_id' => Group::ADMINISTRATOR_ID],
            ],
        ]);
    }

    /** @test */
    public function admin_gets_the_summary()
    {
        $response = $this->send($this->request('GET', '/api/analytics/summary', [
            'authenticatedAs' => self::ADMIN,
        ]));

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);

        foreach (['totalUsers', 'totalDiscussions', 'totalPosts'] as $key) {
            $this->assertArrayHasKey($key, $body);
            $this->assertIsInt($body[$key]);
        }

        $this->assertGreaterThanOrEqual(2, $body['totalUsers']);
    }

    /** @test */
    public function a_normal_user_is_denied()
    {
        $response = $this->send($this->request('GET', '/api/analytics/summary', [
            'authenticatedAs' => self::MEMBER,
        ]));

        $this->assertEquals(403, $response->getStatusCode());
    }
}
