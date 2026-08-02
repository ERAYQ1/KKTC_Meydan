<?php

namespace KktcMeydan\Tests\integration;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Testing\integration\TestCase;

class ReportStatusTest extends TestCase
{
    const AUTHOR = 113;
    const MODERATOR = 114;
    const DISCUSSION = 213;
    const POST = 313;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('kktcmeydan-report-status');

        $now = Carbon::now()->toDateTimeString();

        $this->prepareDatabase([
            'users' => [
                [
                    'id' => self::AUTHOR,
                    'username' => 'sorun_bildiren',
                    'email' => 'sorun@kktcmeydan.test',
                    'password' => 'hashedpassword',
                    'is_email_confirmed' => 1,
                    'joined_at' => $now,
                ],
                [
                    'id' => self::MODERATOR,
                    'username' => 'moderator_kktc',
                    'email' => 'moderator@kktcmeydan.test',
                    'password' => 'hashedpassword',
                    'is_email_confirmed' => 1,
                    'joined_at' => $now,
                ],
            ],
            'group_user' => [
                ['user_id' => self::MODERATOR, 'group_id' => Group::MODERATOR_ID],
            ],
            'discussions' => [
                [
                    'id' => self::DISCUSSION,
                    'title' => 'Alsancak sahil yolunda cukur var',
                    'user_id' => self::AUTHOR,
                    'first_post_id' => self::POST,
                    'last_post_id' => self::POST,
                    'last_posted_user_id' => self::AUTHOR,
                    'comment_count' => 1,
                    'is_private' => 0,
                    'is_approved' => 1,
                    'created_at' => $now,
                    'last_posted_at' => $now,
                    'report_status' => 'bildirildi',
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
                    'content' => '<t><p>Iki gundur duruyor, tehlikeli.</p></t>',
                    'is_private' => 0,
                    'is_approved' => 1,
                ],
            ],
        ]);
    }

    /** @test */
    public function discussion_endpoint_exposes_report_status_attributes()
    {
        $response = $this->send($this->request('GET', '/api/discussions/'.self::DISCUSSION));

        $this->assertEquals(200, $response->getStatusCode());

        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];

        $this->assertEquals('bildirildi', $attributes['reportStatus']);
        $this->assertArrayHasKey('canEditReportStatus', $attributes);
        $this->assertFalse($attributes['canEditReportStatus']);

        $moderatorResponse = $this->send($this->request('GET', '/api/discussions/'.self::DISCUSSION, [
            'authenticatedAs' => self::MODERATOR,
        ]));

        $moderatorAttributes = json_decode((string) $moderatorResponse->getBody(), true)['data']['attributes'];

        $this->assertTrue($moderatorAttributes['canEditReportStatus']);
    }

    /** @test */
    public function forum_endpoint_exposes_status_options()
    {
        $response = $this->send($this->request('GET', '/api'));

        $this->assertEquals(200, $response->getStatusCode());

        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];

        $this->assertIsArray($attributes['reportStatuses']);
        $this->assertContains('bildirildi', $attributes['reportStatuses']);
        $this->assertContains('cozuldu', $attributes['reportStatuses']);
    }
}
