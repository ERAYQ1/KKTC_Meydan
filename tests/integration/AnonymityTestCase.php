<?php

namespace KktcMeydan\Tests\integration;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Testing\integration\TestCase;

/**
 * Anonim paylasim testleri icin ortak fikstur.
 *
 * Kurulan senaryo:
 *   - `serbest` etiketi (anonim paylasima izin verilen tek kategori)
 *   - ANON_AUTHOR: hem anonim hem acik icerigi olan bir kullanici.
 *     Ayni kullanicinin ikisine birden sahip olmasi kritik: testler
 *     "anonim olan gizlendi mi" ile "acik olan hala goruluyor mu" ayrimini
 *     yapabilsin, yoksa her seyi gizleyen bozuk bir yama da testi gecerdi.
 *   - MODERATOR: `discussion.viewIpsPosts` izniyle, gercek kimligi gorebilen.
 */
abstract class AnonymityTestCase extends TestCase
{
    /** Hem anonim hem acik icerigi var - "fazla genis yama" kontrolu icin. */
    const ANON_AUTHOR = 100;
    const OTHER_USER = 101;
    const MODERATOR = 102;

    /**
     * SADECE anonim icerigi olan yazar.
     *
     * `included` sizinti testleri bunu kullanmali: ANON_AUTHOR ayni zamanda
     * acik bir konunun da yazari oldugu icin onun `included` blogunda
     * gorunmesi DOGRU davranistir, sizinti degil. Ayrimi yapamayan bir test
     * ya bos gecer ya da yanlis alarm verir.
     */
    const ANON_ONLY_AUTHOR = 103;

    const TAG_SERBEST = 10;

    const ANON_DISCUSSION = 200;
    const OPEN_DISCUSSION = 201;
    const ANON_ONLY_DISCUSSION = 202;

    const ANON_POST = 300;
    const OPEN_POST = 301;
    const ANON_ONLY_POST = 302;

    protected function setUp(): void
    {
        parent::setUp();

        // flarum-approval, flarum-flags'e bagimli; ikisi de anonim gonderinin
        // telefon tespitinde onay kuyruguna dusme yolunda devrede.
        $this->extension(
            'flarum-tags',
            'flarum-flags',
            'flarum-approval',
            'kktcmeydan-anonymous-posting'
        );

        $now = Carbon::now()->toDateTimeString();

        $this->prepareDatabase([
            'users' => [
                $this->user(self::ANON_AUTHOR, 'anonyazar'),
                $this->user(self::OTHER_USER, 'baskabiri'),
                $this->user(self::MODERATOR, 'moderator'),
                $this->user(self::ANON_ONLY_AUTHOR, 'sadeceanonim'),
            ],
            'groups' => [
                ['id' => Group::MODERATOR_ID, 'name_singular' => 'Mod', 'name_plural' => 'Mods', 'is_hidden' => 0],
            ],
            'group_user' => [
                ['user_id' => self::MODERATOR, 'group_id' => Group::MODERATOR_ID],
            ],
            'group_permission' => [
                ['group_id' => Group::MODERATOR_ID, 'permission' => 'discussion.viewIpsPosts'],
            ],
            'tags' => [
                ['id' => self::TAG_SERBEST, 'name' => 'Genel Meydan', 'slug' => 'serbest', 'position' => 0],
            ],
            'discussions' => [
                [
                    'id' => self::ANON_DISCUSSION,
                    'title' => 'Anonim itiraf konusu',
                    'user_id' => self::ANON_AUTHOR,
                    'first_post_id' => self::ANON_POST,
                    'last_post_id' => self::ANON_POST,
                    'last_posted_user_id' => self::ANON_AUTHOR,
                    'comment_count' => 1,
                    'is_private' => 0,
                    'is_approved' => 1,
                    'is_anonymous' => 1,
                    'created_at' => $now,
                    'last_posted_at' => $now,
                ],
                [
                    'id' => self::OPEN_DISCUSSION,
                    'title' => 'Acik kimlikli konu',
                    'user_id' => self::ANON_AUTHOR,
                    'first_post_id' => self::OPEN_POST,
                    'last_post_id' => self::OPEN_POST,
                    'last_posted_user_id' => self::ANON_AUTHOR,
                    'comment_count' => 1,
                    'is_private' => 0,
                    'is_approved' => 1,
                    'is_anonymous' => 0,
                    'created_at' => $now,
                    'last_posted_at' => $now,
                ],
                [
                    'id' => self::ANON_ONLY_DISCUSSION,
                    'title' => 'Gizli kalsin istedigim mesele',
                    'user_id' => self::ANON_ONLY_AUTHOR,
                    'first_post_id' => self::ANON_ONLY_POST,
                    'last_post_id' => self::ANON_ONLY_POST,
                    'last_posted_user_id' => self::ANON_ONLY_AUTHOR,
                    'comment_count' => 1,
                    'is_private' => 0,
                    'is_approved' => 1,
                    'is_anonymous' => 1,
                    'created_at' => $now,
                    'last_posted_at' => $now,
                ],
            ],
            'discussion_tag' => [
                ['discussion_id' => self::ANON_DISCUSSION, 'tag_id' => self::TAG_SERBEST],
                ['discussion_id' => self::OPEN_DISCUSSION, 'tag_id' => self::TAG_SERBEST],
                ['discussion_id' => self::ANON_ONLY_DISCUSSION, 'tag_id' => self::TAG_SERBEST],
            ],
            'posts' => [
                $this->post(self::ANON_POST, self::ANON_DISCUSSION, 'Anonim itiraf metni burada.', 1, self::ANON_AUTHOR),
                $this->post(self::OPEN_POST, self::OPEN_DISCUSSION, 'Acik kimlikli mesaj metni.', 0, self::ANON_AUTHOR),
                $this->post(self::ANON_ONLY_POST, self::ANON_ONLY_DISCUSSION, 'Gizli kalsin istedigim mesele hakkinda.', 1, self::ANON_ONLY_AUTHOR),
            ],
        ]);
    }

    private function user(int $id, string $username): array
    {
        return [
            'id' => $id,
            'username' => $username,
            'email' => $username.'@kktcmeydan.test',
            'password' => 'hashedpassword',
            'is_email_confirmed' => 1,
            'joined_at' => Carbon::now()->toDateTimeString(),
        ];
    }

    private function post(int $id, int $discussionId, string $content, int $isAnonymous, int $userId): array
    {
        return [
            'id' => $id,
            'discussion_id' => $discussionId,
            'user_id' => $userId,
            'number' => 1,
            'created_at' => Carbon::now()->toDateTimeString(),
            'type' => 'comment',
            'content' => '<t><p>'.$content.'</p></t>',
            'is_private' => 0,
            'is_approved' => 1,
            'is_anonymous' => $isAnonymous,
        ];
    }

    /**
     * Sorgu parametreli GET istegi. `TestCase::request()` query string'i
     * ayristirmiyor, parametreler PSR-7 uzerinden ayrica set edilmeli
     * (Flarum yonlendirici bunlari rota parametreleriyle birlestiriyor,
     * bkz. RouteHandlerFactory).
     */
    protected function apiGet(string $path, array $query = [], ?int $authenticatedAs = null)
    {
        $options = $authenticatedAs !== null ? ['authenticatedAs' => $authenticatedAs] : [];

        return $this->send(
            $this->request('GET', $path, $options)->withQueryParams($query)
        );
    }

    /** Yanit govdesini diziye cevirir. */
    protected function json($response): array
    {
        return json_decode($response->getBody()->getContents(), true) ?? [];
    }

    /** `included` bloğunda bu id ile bir `users` kaydi var mi? */
    protected function includesUser(array $json, int $userId): bool
    {
        foreach ($json['included'] ?? [] as $resource) {
            if (($resource['type'] ?? null) === 'users' && (int) ($resource['id'] ?? 0) === $userId) {
                return true;
            }
        }

        return false;
    }

    /** Ana veri kumesindeki id listesi. */
    protected function dataIds(array $json): array
    {
        $data = $json['data'] ?? [];
        $data = isset($data['type']) ? [$data] : $data;

        return array_map(fn ($r) => (int) $r['id'], $data);
    }
}
