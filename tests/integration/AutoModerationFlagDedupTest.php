<?php

namespace KktcMeydan\Tests\integration;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Testing\integration\TestCase;

/**
 * S7 regresyonu: `GuardRegulatedCategoryContent`'in Flag olusturmasi
 * `firstOrCreate`'e cevrildi. Eskiden her tetiklenen Saving `new Flag`
 * yapiyordu - ayni gonderi ihlali koruyan kategoride birden fazla kez
 * duzenlenirse (orn. moderator onayi da bir Post save'i tetikler), her
 * tetiklenmede yeni bir Flag satiri birikiyordu.
 */
class AutoModerationFlagDedupTest extends TestCase
{
    const ADMIN = 1;
    const TAG_SECURITY = 10;
    const DISCUSSION = 200;
    const POST = 300;

    // Gecerli TC kimlik checksum'i - ContentGuardTest'te de kullanilan ayni
    // deger (10000000146), guard'in kimlik-no dalini guvenilir tetikler.
    const VALID_TC_ID = '10000000146';

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-flags', 'flarum-approval', 'kktcmeydan-auto-moderation');

        $now = Carbon::now()->toDateTimeString();

        $this->prepareDatabase([
            'users' => [
                [
                    'id' => self::ADMIN,
                    'username' => 'yonetici',
                    'email' => 'yonetici@kktcmeydan.test',
                    'password' => 'hashedpassword',
                    'is_email_confirmed' => 1,
                    'joined_at' => $now,
                ],
            ],
            'group_user' => [
                ['user_id' => self::ADMIN, 'group_id' => Group::ADMINISTRATOR_ID],
            ],
            'tags' => [
                ['id' => self::TAG_SECURITY, 'name' => 'Guvenlik/Acil Durum', 'slug' => 'guvenlik-acil-durum', 'position' => 0],
            ],
            'discussions' => [
                [
                    'id' => self::DISCUSSION,
                    'title' => 'Mahalle guvenligi hakkinda',
                    'user_id' => self::ADMIN,
                    'first_post_id' => self::POST,
                    'last_post_id' => self::POST,
                    'last_posted_user_id' => self::ADMIN,
                    'comment_count' => 1,
                    'is_private' => 0,
                    'is_approved' => 1,
                    'created_at' => $now,
                    'last_posted_at' => $now,
                ],
            ],
            'discussion_tag' => [
                ['discussion_id' => self::DISCUSSION, 'tag_id' => self::TAG_SECURITY],
            ],
            'posts' => [
                [
                    'id' => self::POST,
                    'discussion_id' => self::DISCUSSION,
                    'user_id' => self::ADMIN,
                    'number' => 1,
                    'created_at' => $now,
                    'type' => 'comment',
                    'content' => '<t><p>Baslangicta temiz bir mesaj.</p></t>',
                    'is_private' => 0,
                    'is_approved' => 1,
                ],
            ],
        ]);
    }

    private function editContent(string $content)
    {
        return $this->send($this->request('PATCH', '/api/posts/'.self::POST, [
            'authenticatedAs' => self::ADMIN,
            'json' => [
                'data' => [
                    'attributes' => ['content' => $content],
                ],
            ],
        ]));
    }

    private function flagCountForPost(): int
    {
        return $this->database()
            ->table('flags')
            ->where('post_id', self::POST)
            ->where('type', 'kktcmeydan-auto-moderation')
            ->count();
    }

    /** @test */
    public function tekrarlanan_ihlal_tekrar_duzenlemede_ikinci_flag_olusturmuyor()
    {
        $this->editContent('Kimlik numaram '.self::VALID_TC_ID.' dogrulama icin.');

        $this->assertSame(1, $this->flagCountForPost(), 'Ilk ihlalde Flag olusmadi.');

        $this->editContent('Kimlik numaram yine '.self::VALID_TC_ID.' burada.');

        $this->assertSame(1, $this->flagCountForPost(), 'Ikinci tetiklenmede duplike Flag olustu.');
    }
}
