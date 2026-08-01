<?php

namespace KktcMeydan\Tests\integration;

use Carbon\Carbon;
use Flarum\Testing\integration\TestCase;

class BlockUserTest extends TestCase
{
    const ACTOR = 2;
    const TARGET = 3;
    const DISCUSSION = 200;
    const POST = 300;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('kktcmeydan-block-user');

        $now = Carbon::now()->toDateTimeString();

        $this->prepareDatabase([
            'users' => [
                [
                    'id' => self::ACTOR,
                    'username' => 'engelleyen',
                    'email' => 'engelleyen@kktcmeydan.test',
                    'password' => 'hashedpassword',
                    'is_email_confirmed' => 1,
                    'joined_at' => $now,
                ],
                [
                    'id' => self::TARGET,
                    'username' => 'engellenen',
                    'email' => 'engellenen@kktcmeydan.test',
                    'password' => 'hashedpassword',
                    'is_email_confirmed' => 1,
                    'joined_at' => $now,
                ],
            ],
            'discussions' => [
                [
                    'id' => self::DISCUSSION,
                    'title' => 'Engellenen kullanicinin konusu',
                    'user_id' => self::TARGET,
                    'first_post_id' => self::POST,
                    'last_post_id' => self::POST,
                    'last_posted_user_id' => self::TARGET,
                    'comment_count' => 1,
                    'is_private' => 0,
                    'is_approved' => 1,
                    'created_at' => $now,
                    'last_posted_at' => $now,
                ],
            ],
            'posts' => [
                [
                    'id' => self::POST,
                    'discussion_id' => self::DISCUSSION,
                    'user_id' => self::TARGET,
                    'number' => 1,
                    'created_at' => $now,
                    'type' => 'comment',
                    'content' => '<t><p>Merhaba.</p></t>',
                    'is_private' => 0,
                    'is_approved' => 1,
                ],
            ],
        ]);
    }

    private function blockRowExists(): bool
    {
        return $this->database()
            ->table('user_blocks')
            ->where('user_id', self::ACTOR)
            ->where('blocked_user_id', self::TARGET)
            ->exists();
    }

    /** @test */
    public function blocking_a_user_creates_row_and_marks_serializer()
    {
        $this->assertFalse($this->blockRowExists());

        $response = $this->send($this->request('POST', '/api/users/'.self::TARGET.'/block', [
            'authenticatedAs' => self::ACTOR,
        ]));

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);

        $this->assertTrue($body['data']['attributes']['isBlocked']);
        $this->assertTrue($this->blockRowExists());
    }

    /** @test */
    public function unblocking_a_user_removes_row_and_updates_serializer()
    {
        $this->send($this->request('POST', '/api/users/'.self::TARGET.'/block', [
            'authenticatedAs' => self::ACTOR,
        ]));

        $this->assertTrue($this->blockRowExists());

        $response = $this->send($this->request('DELETE', '/api/users/'.self::TARGET.'/block', [
            'authenticatedAs' => self::ACTOR,
        ]));

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);

        $this->assertFalse($body['data']['attributes']['isBlocked']);
        $this->assertFalse($this->blockRowExists());
    }

    /** @test */
    public function a_user_cannot_block_themselves()
    {
        $response = $this->send($this->request('POST', '/api/users/'.self::ACTOR.'/block', [
            'authenticatedAs' => self::ACTOR,
        ]));

        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function blocked_users_discussions_are_hidden_from_the_blocker()
    {
        $this->send($this->request('POST', '/api/users/'.self::TARGET.'/block', [
            'authenticatedAs' => self::ACTOR,
        ]));

        $response = $this->send($this->request('GET', '/api/discussions', [
            'authenticatedAs' => self::ACTOR,
        ]));

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        $ids = array_column($body['data'], 'id');

        $this->assertNotContains((string) self::DISCUSSION, $ids);
    }

    /** @test */
    public function unrelated_users_still_see_the_discussion()
    {
        $response = $this->send($this->request('GET', '/api/discussions'));

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        $ids = array_column($body['data'], 'id');

        $this->assertContains((string) self::DISCUSSION, $ids);
    }
}
