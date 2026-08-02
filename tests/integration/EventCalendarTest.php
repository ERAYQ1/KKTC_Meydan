<?php

namespace KktcMeydan\Tests\integration;

use Carbon\Carbon;
use Flarum\Testing\integration\TestCase;

class EventCalendarTest extends TestCase
{
    const AUTHOR = 111;
    const GUEST_RSVP_USER = 112;
    const DISCUSSION = 211;
    const POST = 311;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('kktcmeydan-event-calendar');

        $now = Carbon::now();
        $nowString = $now->toDateTimeString();

        // Etkinlik "simdi"ye gore konumlaniyor: ListEventsController varsayilan
        // olarak icinde bulunulan ayi tariyor, sabit bir tarih testi ay
        // degisiminde kirilgan hale getirirdi.
        $start = $now->copy()->addHours(2);
        $end = $start->copy()->addHours(3);

        $this->prepareDatabase([
            'users' => [
                [
                    'id' => self::AUTHOR,
                    'username' => 'etkinlik_sahibi',
                    'email' => 'etkinlik@kktcmeydan.test',
                    'password' => 'hashedpassword',
                    'is_email_confirmed' => 1,
                    'joined_at' => $nowString,
                ],
                [
                    'id' => self::GUEST_RSVP_USER,
                    'username' => 'katilimci',
                    'email' => 'katilimci@kktcmeydan.test',
                    'password' => 'hashedpassword',
                    'is_email_confirmed' => 1,
                    'joined_at' => $nowString,
                ],
            ],
            'discussions' => [
                [
                    'id' => self::DISCUSSION,
                    'title' => 'Girne konseri - bu ay',
                    'user_id' => self::AUTHOR,
                    'first_post_id' => self::POST,
                    'last_post_id' => self::POST,
                    'last_posted_user_id' => self::AUTHOR,
                    'comment_count' => 1,
                    'is_private' => 0,
                    'is_approved' => 1,
                    'created_at' => $nowString,
                    'last_posted_at' => $nowString,
                    'event_start_at' => $start->toDateTimeString(),
                    'event_end_at' => $end->toDateTimeString(),
                ],
            ],
            'posts' => [
                [
                    'id' => self::POST,
                    'discussion_id' => self::DISCUSSION,
                    'user_id' => self::AUTHOR,
                    'number' => 1,
                    'created_at' => $nowString,
                    'type' => 'comment',
                    'content' => '<t><p>Girne limaninda acik hava konseri.</p></t>',
                    'is_private' => 0,
                    'is_approved' => 1,
                ],
            ],
            'event_rsvps' => [
                [
                    'discussion_id' => self::DISCUSSION,
                    'user_id' => self::AUTHOR,
                    'status' => 'going',
                    'created_at' => $nowString,
                    'updated_at' => $nowString,
                ],
                [
                    'discussion_id' => self::DISCUSSION,
                    'user_id' => self::GUEST_RSVP_USER,
                    'status' => 'interested',
                    'created_at' => $nowString,
                    'updated_at' => $nowString,
                ],
            ],
        ]);
    }

    /** @test */
    public function discussion_endpoint_exposes_event_attributes()
    {
        $response = $this->send($this->request('GET', '/api/discussions/'.self::DISCUSSION));

        $this->assertEquals(200, $response->getStatusCode());

        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];

        $this->assertNotNull($attributes['eventStartAt']);
        $this->assertNotNull($attributes['eventEndAt']);
        $this->assertLessThan(
            Carbon::parse($attributes['eventEndAt']),
            Carbon::parse($attributes['eventStartAt'])
        );

        $this->assertSame(1, $attributes['rsvpGoingCount']);
        $this->assertSame(1, $attributes['rsvpInterestedCount']);
    }

    /** @test */
    public function events_endpoint_lists_the_event()
    {
        $response = $this->send($this->request('GET', '/api/events'));

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($body['data']);
        $this->assertContains((string) self::DISCUSSION, array_column($body['data'], 'id'));
    }
}
