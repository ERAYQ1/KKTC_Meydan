<?php

/*
 * KKTC Meydan seed script.
 *
 * Applies site_settings.json (forum name/description/locale/theme, tags,
 * cosmetic roles) and creates example users + discussions per category so
 * the forum doesn't look empty on first boot.
 *
 * Roles (Öğrenci/İşletme/Yerel Halk/Güvenilir Üye) are cosmetic badges only
 * - no permissions are granted, admin/mod groups remain the only groups
 * with elevated permissions.
 *
 * Usage (inside the app container):
 *   docker compose exec flarum-app php seed.php
 *
 * Idempotent: safe to run multiple times, existing rows are matched by
 * slug/username and left as-is (tags) or skipped (users/discussions).
 * `custom_less` is the one exception: it's a raw admin-panel setting, not a
 * row matched by slug, so a plain re-run leaves it alone once it's already
 * been set - pass --force to explicitly re-apply assets/theme.less (e.g.
 * after intentionally editing that file). Without --force this only WRITES
 * custom_less the first time (when it's still empty), so it can never
 * silently clobber a theme an admin customized by hand in the admin panel.
 *
 *   docker compose exec flarum-app php seed.php --force
 */

require __DIR__ . '/vendor/autoload.php';

use Flarum\Discussion\Command\StartDiscussion;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Command\PostReply;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Command\CreateTag;
use Flarum\Tags\Tag;
use Flarum\User\User;
use FoF\Polls\Poll;

$site = require __DIR__ . '/site.php';
$app = $site->bootApp();
$container = $app->getContainer();
$bus = $container->make(\Illuminate\Contracts\Bus\Dispatcher::class);

$settingsPath = __DIR__ . '/site_settings.json';
$config = json_decode(file_get_contents($settingsPath), true);

if (! $config) {
    fwrite(STDERR, "site_settings.json okunamadi veya gecersiz JSON.\n");
    exit(1);
}

// --- 1. Forum settings ---------------------------------------------------

/** @var SettingsRepositoryInterface $settings */
$settings = $container->make(SettingsRepositoryInterface::class);

$settings->set('forum_title', $config['site_name']);
$settings->set('forum_description', $config['site_description']);
$settings->set('default_locale', $config['default_locale']);
$settings->set('theme_primary_color', $config['theme_color']);
$settings->set('theme_secondary_color', $config['theme_color']);
$settings->set('welcome_title', $config['welcome_title']);
$settings->set('welcome_message', $config['welcome_subtitle']);
$settings->set('mail_from', $config['mail_from'] ?? 'noreply@kktcmeydan.com');

if (! empty($config['footer_html'])) {
    $settings->set('custom_footer', $config['footer_html']);
}

if (! empty($config['forum_keywords'])) {
    $settings->set('forum_keywords', $config['forum_keywords']);
}

if (! empty($config['seo_social_media_image_path'])) {
    $settings->set('seo_social_media_image_path', $config['seo_social_media_image_path']);
}

if (isset($config['seo_reviewed_post_crawler'])) {
    $settings->set('seo_reviewed_post_crawler', $config['seo_reviewed_post_crawler']);
}

if (isset($config['seo_reviewed_search_engines'])) {
    $settings->set('seo_reviewed_search_engines', $config['seo_reviewed_search_engines']);
}

if (! empty($config['seo_last_review'])) {
    $settings->set('seo_last_review', $config['seo_last_review']);
}

$forceThemeLess = in_array('--force', $argv, true);
$themeLessPath = __DIR__ . '/assets/theme.less';

if (file_exists($themeLessPath)) {
    if ($forceThemeLess || ! $settings->get('custom_less')) {
        $settings->set('custom_less', file_get_contents($themeLessPath));
    } else {
        echo "custom_less zaten ayarli, --force verilmedigi icin ustune yazilmadi.\n";
    }
}

echo "Site ayarlari uygulandi: {$config['site_name']}\n";

// --- 1b. fof/seo schema fixup ---------------------------------------------
// fof/seo's `seo_meta` migration creates `created_at` as NOT NULL with no
// default, but its model extends Flarum\Database\AbstractModel which sets
// `$timestamps = false` - so Eloquent never fills the column itself. Under
// this project's strict SQL mode (config.php `'strict' => true`) that's a
// hard INSERT failure ("Field 'created_at' doesn't have a default value")
// on the very first page view of any tag, not just an edge case. Giving the
// column a DB-level default fixes it without touching vendor/. Safe to
// re-run (MODIFY is idempotent) and a no-op if fof/seo isn't installed.
$db = $container->make(\Illuminate\Database\ConnectionInterface::class);

if ($db->getSchemaBuilder()->hasTable('seo_meta')) {
    $db->statement('ALTER TABLE `seo_meta` MODIFY `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
    echo "fof/seo seo_meta.created_at duzeltmesi uygulandi.\n";
}

// --- 2. System actor (admin) for authored actions -------------------------

/** @var User $actor */
$actor = User::where('id', 1)->first();

if (! $actor) {
    fwrite(STDERR, "ID 1 admin kullanicisi bulunamadi, once kurulumu tamamlayin.\n");
    exit(1);
}

// --- 3. Tags / categories --------------------------------------------------

$tagIds = [];

foreach ($config['categories'] as $position => $cat) {
    $tag = Tag::where('slug', $cat['slug'])->first();

    // One-time rename migration: category slug changed (e.g. emlak -> yasam),
    // find the old tag by its previous slug and repurpose it in place so
    // existing discussions keep their tag association.
    if (! $tag) {
        foreach ($cat['legacy_slugs'] ?? [] as $legacySlug) {
            $tag = Tag::where('slug', $legacySlug)->first();
            if ($tag) {
                $tag->slug = $cat['slug'];
                break;
            }
        }
    }

    if ($tag) {
        $tag->name = $cat['name'];
        $tag->color = $cat['color'];
        $tag->icon = $cat['icon'];
        $tag->description = $cat['description'];
        $tag->position = $position;
        $tag->save();
    } else {
        $tag = $bus->dispatch(new CreateTag($actor, [
            'attributes' => [
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'color' => $cat['color'],
                'icon' => $cat['icon'],
                'description' => $cat['description'],
                'position' => $position,
                'isHidden' => false,
            ],
        ]));
    }

    $tagIds[$cat['slug']] = $tag->id;
    echo "Kategori hazir: {$cat['name']} (#{$tag->id})\n";
}

// --- 3b. Hashtags (secondary tags: no position, optional parent) -----------

$hashtagIds = [];

foreach ($config['hashtags'] ?? [] as $tagData) {
    $tag = Tag::where('slug', $tagData['slug'])->first();

    if (! $tag) {
        $tag = new Tag();
        $tag->slug = $tagData['slug'];
    }

    $tag->name = $tagData['name'];
    $tag->color = $tagData['color'] ?? null;
    $tag->position = null;
    $tag->is_hidden = false;
    $tag->save();

    $hashtagIds[$tagData['slug']] = $tag->id;
    echo "Hashtag hazir: #{$tagData['name']} (#{$tag->id})\n";
}

// Second pass: link parents (parent can be a category slug or another hashtag slug)
foreach ($config['hashtags'] ?? [] as $tagData) {
    if (empty($tagData['parent'])) {
        continue;
    }

    $parentId = $hashtagIds[$tagData['parent']] ?? $tagIds[$tagData['parent']] ?? null;

    if ($parentId) {
        Tag::where('id', $hashtagIds[$tagData['slug']])->update(['parent_id' => $parentId]);
    }
}

// --- 4. Roles (cosmetic groups, no extra permissions - admin stays highest) --

$roleIds = [];

foreach ($config['roles'] ?? [] as $role) {
    $group = Group::where('name_singular', $role['name_singular'])->first();

    if (! $group) {
        $group = new Group();
    }

    $group->name_singular = $role['name_singular'];
    $group->name_plural = $role['name_plural'];
    $group->color = $role['color'];
    $group->icon = $role['icon'];
    $group->is_hidden = false;
    $group->save();

    $roleIds[$role['name_singular']] = $group->id;
    echo "Rol hazir: {$role['name_singular']} (#{$group->id})\n";
}

// --- 5. Example users ----------------------------------------------------

$exampleUsers = [
    ['username' => 'ada_lefkosa', 'email' => 'ada.lefkosa@example.kktcmeydan.test', 'role' => 'Yerel Halk'],
    ['username' => 'mehmet_girne', 'email' => 'mehmet.girne@example.kktcmeydan.test', 'role' => 'Güvenilir Üye'],
    ['username' => 'zeynep_dau', 'email' => 'zeynep.dau@example.kktcmeydan.test', 'role' => 'Öğrenci'],
    [
        'username' => 'can_maguza', 'email' => 'can.maguza@example.kktcmeydan.test', 'role' => 'İşletme',
        'business' => [
            'business_address' => 'Sakarya, Gazimağusa',
            'business_phone' => '+90 392 555 00 00',
            'business_whatsapp' => '+90 542 555 00 00',
            'business_hours' => 'Her gün 08:00 - 22:00',
        ],
    ],
    ['username' => 'aylin_iskele', 'email' => 'aylin.iskele@example.kktcmeydan.test', 'role' => 'Yerel Halk'],
    ['username' => 'hasan_karpaz', 'email' => 'hasan.karpaz@example.kktcmeydan.test', 'role' => 'Öğrenci'],
];

$members = Group::where('id', Group::MEMBER_ID)->first();
$userIds = [];

foreach ($exampleUsers as $u) {
    $user = User::where('username', $u['username'])->first();

    if (! $user) {
        $user = User::register($u['username'], $u['email'], bin2hex(random_bytes(16)));
        $user->is_email_confirmed = true;
        $user->joined_at = Carbon\Carbon::now();
        $user->save();

        if ($members) {
            $user->groups()->attach($members);
        }
    }

    if (isset($roleIds[$u['role']]) && ! $user->groups->contains($roleIds[$u['role']])) {
        $user->groups()->attach($roleIds[$u['role']]);
    }

    if (isset($u['business'])) {
        $user->preferences = array_merge($user->preferences, $u['business']);
        $user->save();
    }

    $userIds[$u['username']] = $user->id;
    echo "Kullanici hazir: {$u['username']} ({$u['role']}) (#{$user->id})\n";
}

// --- 6. Example discussions per category --------------------------------

$seedThreads = [
    [
        'tag' => 'gundem',
        'hashtags' => ['lefkosa'],
        'title' => 'Bugun KKTC genelinde neler oluyor?',
        'body' => "Gundemi burada takip edelim. Onemli bir haber gordugunuzde paylasin, kaynak eklemeyi unutmayin.",
        'author' => 'ada_lefkosa',
        'reply' => 'Ercan havalimaninda bugun yogunluk varmis, yola cikacaklar erken gitsin.',
        'replyAuthor' => 'mehmet_girne',
    ],
    [
        'tag' => 'gundem',
        'hashtags' => ['elektrik-su'],
        'title' => 'Planli elektrik kesintileri hangi bolgelerde, ne zaman?',
        'body' => "Kurumun yayinladigi kesinti programini takip edip guncel bolge/saat bilgisini burada paylasalim.",
        'author' => 'mehmet_girne',
        'reply' => 'Girne merkez bu hafta sali ogleden sonra kesinti varmis, resmi duyuruyu gordum.',
        'replyAuthor' => 'aylin_iskele',
    ],
    [
        'tag' => 'universite',
        'hashtags' => ['dau', 'ydu'],
        'title' => 'DAU ve YDU icin donem basi ders notu paylasim konusu',
        'body' => "Ders notlariniz, gecmis sinav sorulariniz varsa buraya birakabilirsiniz. Bolumunuzu belirtmeyi unutmayin.",
        'author' => 'zeynep_dau',
        'reply' => 'Bilgisayar muhendisligi 2. sinif icin veri yapilari notlarim var, paylasirim.',
        'replyAuthor' => 'can_maguza',
    ],
    [
        'tag' => 'universite',
        'hashtags' => ['uku', 'lau'],
        'title' => 'UKU ve LAU ogrencileri icin kayit yenileme sureci nasil isliyor?',
        'body' => "Bu donem kayit yenileme tarihlerini ve online sistem uzerinden yasadiginiz sorunlari paylasin, birbirimize yardimci olalim.",
        'author' => 'hasan_karpaz',
        'reply' => 'Ogrenci isleri ofisine gitmeden online sistemden hallettim, 10 dakika surdu.',
        'replyAuthor' => 'zeynep_dau',
    ],
    [
        'tag' => 'yasam',
        'hashtags' => ['girne'],
        'title' => 'Girne merkezde ogrenciye uygun kiralik daire arayanlar',
        'body' => "Girne merkeze yakin, esyali, tek+bir veya iki+bir daire arayanlar burada bilgi paylassin.",
        'author' => 'mehmet_girne',
        'reply' => 'Karakum bolgesinde uygun fiyatli birkac secenek gordum, ilan linkini atarim.',
        'replyAuthor' => 'ada_lefkosa',
    ],
    [
        'tag' => 'yasam',
        'hashtags' => ['gazimagusa', 'dau'],
        'title' => 'Magusa DAU cevresinde yurt mu apartman mi daha mantikli?',
        'body' => "Ilk yil ogrencileri icin butce ve ulasim acisindan yurt/apartman karsilastirmasi yapalim, deneyimlerinizi yazin.",
        'author' => 'can_maguza',
        'reply' => 'Ilk yil yurtta kaldim, ikinci yil arkadaslarla apartmana gectik, daha ekonomik oldu.',
        'replyAuthor' => 'hasan_karpaz',
    ],
    [
        'tag' => 'yasam',
        'hashtags' => ['gazimagusa'],
        'title' => 'Donem sonu tasinacaklar icin ikinci el esya ilanlari',
        'body' => "Okul bitince ya da yurt degistirirken elden cikaracaginiz esyalari buraya yazabilirsiniz.",
        'author' => 'can_maguza',
        'reply' => 'Az kullanilmis mini buzdolabi ve masa satiyorum, Magusa icinde teslim ederim.',
        'replyAuthor' => 'zeynep_dau',
    ],
    [
        'tag' => 'yasam',
        'hashtags' => ['lefkosa'],
        'title' => 'Ikinci el vasita alirken KKTCde dikkat edilmesi gerekenler?',
        'body' => "Gumruk durumu, muayene ve sigorta konusunda tecrubesi olanlar paylasabilir mi?",
        'author' => 'ada_lefkosa',
        'reply' => 'Muayeneden gecmis ve gumruklu arac almak uzun vadede bas agrisini azaltiyor.',
        'replyAuthor' => 'mehmet_girne',
    ],
    [
        'tag' => 'ulasim',
        'hashtags' => ['ercan-havalimani'],
        'title' => 'Ercan - sehir merkezi otobus ve minibus saatleri',
        'body' => "Guncel sefer saatlerini ve fiyatlarini burada paylasip guncel tutalim.",
        'author' => 'zeynep_dau',
        'reply' => 'Lefkosa - Ercan hatti sabah 06:00dan itibaren saat basi kalkiyor.',
        'replyAuthor' => 'mehmet_girne',
    ],
    [
        'tag' => 'ulasim',
        'hashtags' => ['trafik', 'girne'],
        'title' => 'Girne - Lefkosa otoyolunda yogun saatler ve alternatif guzergahlar',
        'body' => "Sabah/aksam yogunluguna takilmamak icin kullandiginiz alternatif yollari paylasir misiniz?",
        'author' => 'aylin_iskele',
        'reply' => 'Sabah 8den once cikinca otoyol rahat oluyor, sonrasi Ozankoy civari tikaniyor.',
        'replyAuthor' => 'can_maguza',
    ],
    [
        'tag' => 'serbest',
        'hashtags' => ['girne'],
        'title' => 'KKTCde en sevdiginiz kahvalti / kahve mekanlari?',
        'body' => "Yerel onerilerinizi paylasin, yeni gelenler icin faydali olur.",
        'author' => 'ada_lefkosa',
        'reply' => 'Girne limaninda sahil kenarindaki kucuk kahvaltici cok iyi, tavsiye ederim.',
        'replyAuthor' => 'can_maguza',
    ],
    [
        'tag' => 'serbest',
        'hashtags' => ['karpaz'],
        'title' => 'Karpaz bolgesine gunubirlik gezi onerileri',
        'body' => "Golden Beach, Kantara Kalesi ve cevresinde gezilecek yerler + yemek onerilerinizi paylasin.",
        'author' => 'hasan_karpaz',
        'reply' => 'Golden Beach oncesi Dipkarpaz kasabasindaki balik lokantasini kacirmayin.',
        'replyAuthor' => 'aylin_iskele',
    ],
    [
        'tag' => 'sorun-bildir',
        'hashtags' => ['girne'],
        'report_status' => 'bildirildi',
        'title' => 'Alsancak sahil yolunda buyuk bir cukur var',
        'body' => "Konum: Alsancak sahil yolu, eczane karsisi. Fotograf: (eklenecek). Iki gundur duruyor, arac lastigine zarar verebilir.",
        'author' => 'mehmet_girne',
        'reply' => 'Ben de gordum, gece karanlikta fark edilmiyor, tehlikeli.',
        'replyAuthor' => 'ada_lefkosa',
    ],
    [
        'tag' => 'sorun-bildir',
        'hashtags' => ['lefkosa'],
        'report_status' => 'inceleniyor',
        'title' => 'Gonyeli meydaninda sokak lambalari bir haftadir yanmiyor',
        'body' => "Konum: Gonyeli meydani ve cevresi. Aksam saatlerinde yayalar icin guvenlik riski olusturuyor.",
        'author' => 'ada_lefkosa',
        'reply' => 'Belediyeye bildirdim, ekip gonderileceği soylendi, takipteyim.',
        'replyAuthor' => 'zeynep_dau',
    ],
    [
        'tag' => 'sorun-bildir',
        'hashtags' => ['gazimagusa'],
        'report_status' => 'cozuldu',
        'title' => 'Sakarya bolgesinde 3 gundur su kesintisi vardi',
        'body' => "Konum: Sakarya, Gazimagusa. Bildirimden 3 gun sonra su verildi, cozuldu olarak isaretliyorum.",
        'author' => 'can_maguza',
        'reply' => 'Bizim sokakta da dun aksam su geldi, tesekkurler bilgi icin.',
        'replyAuthor' => 'hasan_karpaz',
    ],
    [
        'tag' => 'yasam',
        'hashtags' => ['girne', 'kiralik'],
        'title' => 'ILAN: Girne Alsancakta esyali 1+1 kiralik daire',
        'body' => "📍 Konum: Alsancak, Girne (sahile 5 dk yurume)\n💰 Fiyat: 9.000 TL/ay + aidat\n📞 Iletisim: DM ile ulasin\n📅 Ilan tarihi: bugun\n\nEsyali, faturalar haric, minimum 6 ay kontrat.",
        'author' => 'mehmet_girne',
        'reply' => 'Fotograflari da paylasabilir misiniz?',
        'replyAuthor' => 'aylin_iskele',
    ],
    [
        'tag' => 'yasam',
        'hashtags' => ['lefkosa', 'satilik'],
        'title' => 'ILAN: Lefkosa Gonyelide satilik 3+1 daire',
        'body' => "📍 Konum: Gonyeli, Lefkosa\n💰 Fiyat: 65.000 GBP\n📞 Iletisim: DM ile ulasin\n📅 Ilan tarihi: bugun\n\nSitede, otoparkli, 120m2, esyasiz teslim.",
        'author' => 'ada_lefkosa',
        'reply' => 'Site aidati ne kadar aciklayabilir misiniz?',
        'replyAuthor' => 'zeynep_dau',
    ],
    [
        'tag' => 'yasam',
        'hashtags' => ['gazimagusa', 'is-ilani'],
        'title' => 'ILAN: Magusada part-time kafe eleman araniyor',
        'body' => "📍 Bolge: Gazimagusa merkez\n💼 Pozisyon: Barista / kafe elemani\n💰 Maas: Saatlik + bahsis\n🕐 Calisma sekli: Part-time, ogrenciye uygun\n📞 Iletisim: DM ile ulasin\n📅 Ilan tarihi: bugun",
        'author' => 'can_maguza',
        'reply' => 'Ders programina gore saat ayarlanabiliyor mu?',
        'replyAuthor' => 'hasan_karpaz',
    ],
    [
        'tag' => 'yasam',
        'hashtags' => ['dau', 'ev-arkadasi'],
        'title' => 'ILAN: DAU cevresinde ev arkadasi araniyor',
        'body' => "📍 Konum: Magusa, DAU'ya yurume mesafesi\n💰 Fiyat: Kisi basi 4.500 TL + faturalar\n📞 Iletisim: DM ile ulasin\n📅 Ilan tarihi: bugun\n\nDuzenli, sigara icmeyen, muhendislik ogrencisi tercih sebebi.",
        'author' => 'zeynep_dau',
        'reply' => 'Ev kac kisilik, oda bos mu simdiden?',
        'replyAuthor' => 'can_maguza',
    ],
];

foreach ($seedThreads as $thread) {
    $existing = \Flarum\Discussion\Discussion::where('title', $thread['title'])->first();

    if ($existing) {
        echo "Konu zaten var, atlaniyor: {$thread['title']}\n";
        continue;
    }

    $author = User::find($userIds[$thread['author']]);

    $tagData = [
        ['type' => 'tags', 'id' => (string) $tagIds[$thread['tag']]],
    ];

    foreach ($thread['hashtags'] ?? [] as $hashtagSlug) {
        $tagData[] = ['type' => 'tags', 'id' => (string) $hashtagIds[$hashtagSlug]];
    }

    $discussion = $bus->dispatch(new StartDiscussion($author, [
        'attributes' => [
            'title' => $thread['title'],
            'content' => $thread['body'],
        ],
        'relationships' => [
            'tags' => [
                'data' => $tagData,
            ],
        ],
    ], '127.0.0.1'));

    if (isset($thread['report_status'])) {
        $discussion->report_status = $thread['report_status'];
        $discussion->save();
    }

    $replyAuthor = User::find($userIds[$thread['replyAuthor']]);

    $bus->dispatch(new PostReply(
        $discussion->id,
        $replyAuthor,
        ['attributes' => ['content' => $thread['reply']]],
        '127.0.0.1'
    ));

    echo "Konu olusturuldu: {$thread['title']} (#{$discussion->id})\n";
}

// --- 7. Helpers for the bulk user/content generator -----------------------

function kktcSlug(string $text): string
{
    $map = [
        'Ç' => 'C', 'ç' => 'c', 'Ğ' => 'G', 'ğ' => 'g', 'İ' => 'I', 'ı' => 'i',
        'Ö' => 'O', 'ö' => 'o', 'Ş' => 'S', 'ş' => 's', 'Ü' => 'U', 'ü' => 'u',
    ];
    $text = strtolower(strtr($text, $map));
    $text = preg_replace('/[^a-z0-9]+/', '_', $text);

    return trim($text, '_');
}

function kktcUniqueUsername(string $base, array &$used): string
{
    $slug = $base;
    $i = 1;

    while (isset($used[$slug])) {
        $i++;
        $slug = $base . $i;
    }

    $used[$slug] = true;

    return $slug;
}

function kktcEnsureUser(string $username, string $email, ?int $roleId, ?Group $members, array $preferences = []): User
{
    $user = User::where('username', $username)->first();

    if (! $user) {
        $user = User::register($username, $email, bin2hex(random_bytes(16)));
        $user->is_email_confirmed = true;
        $user->joined_at = Carbon\Carbon::now()->subDays(random_int(1, 500));
        $user->save();

        if ($members) {
            $user->groups()->attach($members);
        }
    }

    if ($roleId && ! $user->groups->contains($roleId)) {
        $user->groups()->attach($roleId);
    }

    if ($preferences) {
        $user->preferences = array_merge($user->preferences, $preferences);
        $user->save();
    }

    return $user;
}

function kktcTagRelationships(array $slugs, array $tagIds, array $hashtagIds): array
{
    $data = [];

    foreach ($slugs as $slug) {
        $id = $tagIds[$slug] ?? $hashtagIds[$slug] ?? null;

        if ($id) {
            $data[] = ['type' => 'tags', 'id' => (string) $id];
        }
    }

    return $data;
}

/**
 * Creates a discussion (with optional extension fields + replies) unless a
 * discussion with the same title already exists. Returns null when skipped
 * so callers can skip dependent work (likes/polls/best-answer) too.
 */
function kktcCreateDiscussion(array $p, $bus, array $userIds, array $tagIds, array $hashtagIds): ?array
{
    if (Discussion::where('title', $p['title'])->exists()) {
        return null;
    }

    $author = User::find($userIds[$p['author']]);

    $discussion = $bus->dispatch(new StartDiscussion($author, [
        'attributes' => ['title' => $p['title'], 'content' => $p['body']],
        'relationships' => ['tags' => ['data' => kktcTagRelationships($p['tags'], $tagIds, $hashtagIds)]],
    ], '127.0.0.1'));

    if (! empty($p['extra'])) {
        foreach ($p['extra'] as $column => $value) {
            $discussion->{$column} = $value;
        }
        $discussion->save();
    }

    $posts = [];
    $firstPost = Post::where('discussion_id', $discussion->id)->where('number', 1)->first();

    if ($firstPost) {
        // Konu duzeyindeki bazi bayraklar ilk gonderide de tutulmali. Ozellikle
        // `is_anonymous`: sadece discussion'a yazilinca liste gorunumu "Anonim
        // Uye" derken konuyu acinca ilk mesajda gercek yazar goruluyordu.
        foreach ($p['firstPostExtra'] ?? [] as $column => $value) {
            $firstPost->{$column} = $value;
        }

        if (! empty($p['firstPostExtra'])) {
            $firstPost->save();
        }

        $posts[] = $firstPost;
    }

    foreach ($p['replies'] ?? [] as $reply) {
        $replyAuthor = User::find($userIds[$reply['author']]);

        $posts[] = $bus->dispatch(new PostReply(
            $discussion->id,
            $replyAuthor,
            ['attributes' => ['content' => $reply['body']]],
            '127.0.0.1'
        ));
    }

    echo "Konu olusturuldu: {$p['title']} (#{$discussion->id}, " . count($posts) . " gonderi)\n";

    return ['discussion' => $discussion, 'posts' => $posts];
}

function kktcAttachRandomLikes(array $posts, array $allUsernames, array $userIds): void
{
    foreach ($posts as $post) {
        $candidates = array_values(array_filter(
            $allUsernames,
            fn ($username) => $userIds[$username] !== $post->user_id
        ));

        shuffle($candidates);
        $liked = array_slice($candidates, 0, random_int(0, min(6, count($candidates))));

        foreach ($liked as $username) {
            try {
                $post->likes()->attach($userIds[$username], ['created_at' => Carbon\Carbon::now()]);
            } catch (\Throwable $e) {
                // Already liked (rerun) - ignore.
            }
        }
    }
}

// --- 8. 150 kullanicilik gercekci havuz (144 yeni + 6 mevcut ornek) -------

$maleFirstNames = [
    'Ahmet', 'Mehmet', 'Mustafa', 'Ali', 'Hasan', 'Huseyin', 'Ibrahim', 'Emre',
    'Burak', 'Kemal', 'Serkan', 'Tolga', 'Onur', 'Cem', 'Deniz', 'Kaan', 'Ugur',
    'Baris', 'Volkan', 'Erhan', 'Murat', 'Tarkan', 'Gokhan', 'Ozan', 'Berk',
];

$femaleFirstNames = [
    'Ayse', 'Fatma', 'Zeynep', 'Elif', 'Emine', 'Meryem', 'Selin', 'Gul',
    'Aylin', 'Derya', 'Nazli', 'Ceren', 'Buse', 'Ece', 'Ipek', 'Melis',
    'Sevgi', 'Yasemin', 'Duygu', 'Pinar', 'Sibel', 'Gizem', 'Naz', 'Damla', 'Esra',
];

$lastNames = [
    'Yilmaz', 'Kaya', 'Demir', 'Sahin', 'Celik', 'Yildiz', 'Aydin', 'Ozturk',
    'Arslan', 'Dogan', 'Kilic', 'Aslan', 'Cetin', 'Kara', 'Koc', 'Kurt',
    'Ozdemir', 'Simsek', 'Yildirim', 'Erdogan', 'Gunes', 'Bulut', 'Aksoy',
    'Polat', 'Uzun', 'Ates', 'Kaplan',
];

$universities = [
    'dau' => 'DAU', 'ydu' => 'YDU', 'uku' => 'UKU', 'lau' => 'LAU', 'gau' => 'GAU',
    'odtu-kkk' => 'ODTU KKK', 'bau-kibris' => 'BAU Kibris',
    'girne-universitesi' => 'Girne Universitesi', 'kau' => 'KAU', 'arucad' => 'ARUCAD',
];

$departments = [
    'Bilgisayar Muhendisligi', 'Isletme', 'Hukuk', 'Turizm ve Otel Isletmeciligi',
    'Mimarlik', 'Psikoloji', 'Hemsirelik', 'Uluslararasi Iliskiler',
    'Elektrik-Elektronik Muhendisligi', 'Iletisim', 'Dis Hekimligi', 'Grafik Tasarim',
];

$districts = [
    'lefkosa' => 'Lefkosa', 'girne' => 'Girne', 'gazimagusa' => 'Gazimagusa',
    'guzelyurt' => 'Guzelyurt', 'iskele' => 'Iskele', 'lefke' => 'Lefke',
];

$businessTemplates = [
    'kafe' => ['Sahil Kafe', 'Lokma Cafe', 'Kahve Duragi', 'Nar Kafe', 'Liman Kahvesi'],
    'arac-kiralama' => ['Ada Rent A Car', 'Kibris Oto Kiralama', 'Hizli Rent A Car'],
    'emlak' => ['Ada Emlak', 'Kibris Gayrimenkul', 'Meydan Emlak', 'Guven Emlak'],
    'taksi' => ['Merkez Taksi Duragi', 'Guven Taksi', 'Ada Taksi'],
    'ozel-ders' => ['Basari Ozel Ders', 'Akademi Ozel Ders'],
    'restoran' => ['Liman Restoran', 'Ada Sofrasi', 'Bahce Restoran'],
    'kuafor' => ['Sik Kuafor', 'Ada Berber', 'Stil Kuafor'],
];

// Deterministic RNG: reruns must regenerate the exact same 144 identities
// (name/university/district/business pick + every value baked into a
// discussion title) so kktcEnsureUser/kktcCreateDiscussion's existence
// checks actually match and skip instead of creating duplicates. mt_rand()
// and array_rand() honor mt_srand(); random_int() deliberately does not, so
// only mt_rand() is used below for anything that affects an identity or a
// discussion title.
mt_srand(20260726);

$usedUsernames = array_flip(array_keys($userIds));
$studentPool = [];
$localPool = [];
$businessPool = [];

// 70 ogrenci
for ($i = 0; $i < 70; $i++) {
    $isFemale = mt_rand(0, 1) === 1;
    $first = $isFemale ? $femaleFirstNames[array_rand($femaleFirstNames)] : $maleFirstNames[array_rand($maleFirstNames)];
    $last = $lastNames[array_rand($lastNames)];
    $uniSlug = array_rand($universities);
    $dept = $departments[array_rand($departments)];

    $username = kktcUniqueUsername(kktcSlug($first) . '_' . kktcSlug($uniSlug), $usedUsernames);
    $user = kktcEnsureUser($username, "{$username}@example.kktcmeydan.test", $roleIds['Öğrenci'] ?? null, $members);

    $userIds[$username] = $user->id;
    $studentPool[] = [
        'username' => $username, 'first' => $first, 'last' => $last,
        'university' => $universities[$uniSlug], 'universitySlug' => $uniSlug, 'department' => $dept,
    ];
}
echo "70 ogrenci kullanicisi hazir.\n";

// 50 yerel halk
for ($i = 0; $i < 50; $i++) {
    $isFemale = mt_rand(0, 1) === 1;
    $first = $isFemale ? $femaleFirstNames[array_rand($femaleFirstNames)] : $maleFirstNames[array_rand($maleFirstNames)];
    $last = $lastNames[array_rand($lastNames)];
    $districtSlug = array_rand($districts);
    $roleName = mt_rand(1, 100) <= 20 ? 'Güvenilir Üye' : 'Yerel Halk';

    $username = kktcUniqueUsername(kktcSlug($first) . '_' . kktcSlug($districtSlug), $usedUsernames);
    $user = kktcEnsureUser($username, "{$username}@example.kktcmeydan.test", $roleIds[$roleName] ?? null, $members);

    $userIds[$username] = $user->id;
    $localPool[] = ['username' => $username, 'first' => $first, 'last' => $last, 'district' => $districts[$districtSlug], 'districtSlug' => $districtSlug];
}
echo "50 yerel halk kullanicisi hazir.\n";

// 24 isletme
$businessKeys = array_keys($businessTemplates);
for ($i = 0; $i < 24; $i++) {
    $typeKey = $businessKeys[$i % count($businessKeys)];
    $name = $businessTemplates[$typeKey][array_rand($businessTemplates[$typeKey])];
    $districtSlug = array_rand($districts);
    $districtName = $districts[$districtSlug];

    $username = kktcUniqueUsername(kktcSlug($name), $usedUsernames);
    $phone = sprintf('+90 392 %03d %02d %02d', mt_rand(200, 899), mt_rand(10, 99), mt_rand(10, 99));
    $whatsapp = sprintf('+90 5%02d %03d %02d %02d', mt_rand(30, 59), mt_rand(100, 999), mt_rand(10, 99), mt_rand(10, 99));

    $user = kktcEnsureUser($username, "{$username}@example.kktcmeydan.test", $roleIds['İşletme'] ?? null, $members, [
        'business_address' => "{$districtName} merkez",
        'business_phone' => $phone,
        'business_whatsapp' => $whatsapp,
        'business_hours' => 'Her gun 09:00 - 21:00',
    ]);

    $userIds[$username] = $user->id;
    $businessPool[] = [
        'username' => $username, 'name' => $name, 'type' => $typeKey,
        'district' => $districtName, 'districtSlug' => $districtSlug, 'phone' => $phone,
    ];
}
echo "24 isletme kullanicisi hazir.\n";
echo "Toplam kullanici havuzu: " . count($userIds) . "\n";

$allUsernames = array_keys($userIds);

// --- 9. Ilanlar (classifieds) ---------------------------------------------

$classifiedTypeMap = [
    'satilik' => ['hashtag' => 'satilik', 'currency' => 'GBP', 'min' => 40000, 'max' => 120000],
    'kiralik' => ['hashtag' => 'kiralik', 'currency' => 'TRY', 'min' => 6000, 'max' => 18000],
    'is_ilani' => ['hashtag' => 'is-ilani', 'currency' => 'TRY', 'min' => 15000, 'max' => 35000],
    'ev_arkadasi' => ['hashtag' => 'ev-arkadasi', 'currency' => 'TRY', 'min' => 3500, 'max' => 6000],
    'ikinci_el' => ['hashtag' => 'ikinci-el', 'currency' => 'TRY', 'min' => 500, 'max' => 12000],
];

$classifiedTitles = [
    'satilik' => 'ILAN: %s bolgesinde satilik %s',
    'kiralik' => 'ILAN: %s bolgesinde kiralik %s',
    'is_ilani' => 'ILAN: %s bolgesinde %s araniyor',
    'ev_arkadasi' => 'ILAN: %s bolgesinde ev arkadasi araniyor (%s)',
    'ikinci_el' => 'ILAN: %s bolgesinde ikinci el %s satiliyor',
];

$propertyKinds = ['1+1 daire', '2+1 daire', '3+1 daire', 'stüdyo daire', 'mustakil ev'];
$jobKinds = ['garson', 'barista', 'resepsiyonist', 'satis danismani', 'temizlik personeli'];
$secondHandItems = ['mini buzdolabi', 'calisma masasi', 'bisiklet', 'laptop standi', 'yatak seti'];

$likeableSeedPosts = [];

for ($i = 0; $i < 32; $i++) {
    $type = array_keys($classifiedTypeMap)[$i % count($classifiedTypeMap)];
    $meta = $classifiedTypeMap[$type];

    $author = $type === 'is_ilani' && $businessPool ? $businessPool[$i % count($businessPool)]
        : ($localPool ? $localPool[$i % count($localPool)] : null);

    if (! $author) {
        continue;
    }

    $district = $author['district'] ?? $districts[array_rand($districts)];
    $itemLabel = match ($type) {
        'satilik', 'kiralik' => $propertyKinds[$i % count($propertyKinds)],
        'is_ilani' => $jobKinds[$i % count($jobKinds)],
        'ev_arkadasi' => 'kisi basi ' . mt_rand(3500, 6000) . ' TL',
        'ikinci_el' => $secondHandItems[$i % count($secondHandItems)],
        default => 'ilan',
    };

    $title = sprintf($classifiedTitles[$type], $district, $itemLabel);
    $price = random_int($meta['min'], $meta['max']);
    $phone = $author['phone'] ?? sprintf('+90 5%02d %03d %02d %02d', random_int(30, 59), random_int(100, 999), random_int(10, 99), random_int(10, 99));

    $replyAuthor = $studentPool[$i % count($studentPool)]['username'] ?? $author['username'];

    $result = kktcCreateDiscussion([
        'title' => $title,
        'body' => "📍 Konum: {$district}\n💰 Fiyat: {$price} {$meta['currency']}\n📞 Iletisim: {$phone}\n\nDetaylar icin yorum birakin veya DM atin.",
        'author' => $author['username'],
        'tags' => ['yasam', $meta['hashtag'], $author['districtSlug'] ?? array_rand($districts)],
        'extra' => [
            'price' => (float) $price,
            'currency' => $meta['currency'],
            'location' => $district,
            'contact_phone' => $phone,
            'classified_type' => $type,
        ],
        'replies' => [
            ['author' => $replyAuthor, 'body' => 'Merhaba, hala guncel mi? Detay alabilir miyim?'],
        ],
    ], $bus, $userIds, $tagIds, $hashtagIds);

    if ($result) {
        $likeableSeedPosts = array_merge($likeableSeedPosts, $result['posts']);
    }
}

// --- 10. Sorun bildirimleri (report-status) -------------------------------

$reportStatuses = ['bildirildi', 'inceleniyor', 'yetkiliye-iletildi', 'cozuldu'];
$reportProblems = [
    'yolda buyuk bir cukur var', 'sokak lambalari bir haftadir yanmiyor',
    'kaldirimlar yayalar icin tehlikeli halde', 'trafik isigi arizali',
    'coplerin toplanmasinda gecikme yasaniyor', 'planli olmayan su kesintisi var',
];

for ($i = 0; $i < 24; $i++) {
    $status = $reportStatuses[$i % count($reportStatuses)];
    $problem = $reportProblems[$i % count($reportProblems)];
    $author = $localPool[($i + 5) % count($localPool)];
    $district = $author['district'];

    $result = kktcCreateDiscussion([
        'title' => "{$district} bolgesinde: {$problem} (#" . ($i + 1) . ')',
        'body' => "Konum: {$district} merkez ve cevresi. Durum: {$problem}. Guncel bilgisi olan paylasabilir mi?",
        'author' => $author['username'],
        'tags' => ['sorun-bildir', $author['districtSlug']],
        'extra' => ['report_status' => $status],
        'replies' => [
            ['author' => $localPool[($i + 12) % count($localPool)]['username'], 'body' => 'Ben de gordum, umarim kisa surede cozulur.'],
        ],
    ], $bus, $userIds, $tagIds, $hashtagIds);

    if ($result) {
        $likeableSeedPosts = array_merge($likeableSeedPosts, $result['posts']);
    }
}

// --- 11. Etkinlikler (event-calendar) -------------------------------------

$eventKinds = [
    ['tag' => 'konser', 'label' => 'konseri'],
    ['tag' => 'festival', 'label' => 'festivali'],
    ['tag' => 'workshop', 'label' => 'atolyesi'],
    ['tag' => 'etkinlik', 'label' => 'etkinligi'],
];

for ($i = 0; $i < 24; $i++) {
    $kind = $eventKinds[$i % count($eventKinds)];
    $district = $districts[array_rand($districts)];
    $author = $businessPool ? $businessPool[$i % count($businessPool)]['username'] : $localPool[$i % count($localPool)]['username'];
    // Anchored to a fixed date (not Carbon::now()) so the generated title/date
    // stays byte-identical on reruns regardless of which day the script runs.
    $start = Carbon\Carbon::parse('2026-08-01')->addDays(mt_rand(7, 150))->setTime(mt_rand(10, 20), [0, 30][mt_rand(0, 1)]);
    $end = (clone $start)->addHours(mt_rand(2, 6));

    $result = kktcCreateDiscussion([
        'title' => "{$district} {$kind['label']} - " . $start->format('d.m.Y'),
        'body' => "📍 Yer: {$district}\n📅 Tarih: {$start->format('d.m.Y H:i')} - {$end->format('H:i')}\n\nDetaylar ve bilet bilgisi icin takipte kalin.",
        'author' => $author,
        'tags' => ['gundem', $kind['tag']],
        'extra' => [
            'event_start_at' => $start,
            'event_end_at' => $end,
        ],
        'replies' => [
            ['author' => $studentPool[$i % count($studentPool)]['username'], 'body' => 'Gitmeyi dusunuyorum, katilan var mi?'],
        ],
    ], $bus, $userIds, $tagIds, $hashtagIds);

    if ($result) {
        $likeableSeedPosts = array_merge($likeableSeedPosts, $result['posts']);
    }
}

// --- 12. Anonim konular (anonymous-posting, sadece Genel Meydan/serbest) --

$anonymousTopics = [
    'Bir itirafim var, kimseye soyleyemedim',
    'Sinav stresiyle nasil basa cikiyorsunuz?',
    'Gece uykusuzlugunda aklima takilan bir konu',
    'Iliski tavsiyesine ihtiyacim var',
    'Aileme soyleyemedigim bir karar aldim',
    'Universitede yalniz hissediyorum, oneri arayan var mi',
];

for ($i = 0; $i < 18; $i++) {
    $topic = $anonymousTopics[$i % count($anonymousTopics)];
    $author = array_merge($studentPool, $localPool)[$i % (count($studentPool) + count($localPool))]['username'];

    $result = kktcCreateDiscussion([
        'title' => $topic . ' (#' . ($i + 1) . ')',
        'body' => "Burada kimlik gizli kaliyor, rahatca yazabilirsiniz. Konu: {$topic}",
        'author' => $author,
        'tags' => ['serbest'],
        'extra' => ['is_anonymous' => true],
        'firstPostExtra' => ['is_anonymous' => true],
        'replies' => [
            ['author' => $localPool[($i + 7) % count($localPool)]['username'], 'body' => 'Yalniz degilsin, bu toplulukta konusabilirsin.'],
        ],
    ], $bus, $userIds, $tagIds, $hashtagIds);

    if ($result) {
        $likeableSeedPosts = array_merge($likeableSeedPosts, $result['posts']);
    }
}

// --- 13. Genel dolgulama konulari (gundem/universite/ulasim/yasam/serbest) -

$fillerTemplates = [
    ['tag' => 'universite', 'title' => '%2$s dersi icin kaynak onerisi (%1$s)', 'body' => '%s okuyanlar, %s dersi icin faydali kaynak/kitap onerebilir mi?'],
    ['tag' => 'universite', 'title' => '%s mezunlari is hayatinda nasil, deneyim paylasir misiniz?', 'body' => '%s mezunu olup sektorde calisanlar tecrubelerini paylasabilir mi?'],
    ['tag' => 'gundem', 'title' => '%s bolgesinde bugun trafik nasil?', 'body' => '%s tarafinda yasayanlar guncel trafik durumunu paylasabilir mi?'],
    ['tag' => 'ulasim', 'title' => '%s - sehir merkezi arasi ulasim onerileri', 'body' => '%s bolgesinden merkeze en pratik ulasim yolunu paylasir misiniz?'],
    ['tag' => 'serbest', 'title' => '%s bolgesinde vakit gecirmek icin nereyi onerirsiniz?', 'body' => '%s civarinda gezilecek, oturulacak yerler icin onerilerinizi bekliyoruz.'],
];

for ($i = 0; $i < 45; $i++) {
    $tpl = $fillerTemplates[$i % count($fillerTemplates)];

    if ($tpl['tag'] === 'universite') {
        $s = $studentPool[$i % count($studentPool)];
        $title = sprintf($tpl['title'], $s['university'], $s['department']);
        $body = sprintf($tpl['body'], $s['university'], $s['department']);
        $author = $s['username'];
        $tags = ['universite', $s['universitySlug']];
    } else {
        $l = $localPool[$i % count($localPool)];
        $title = sprintf($tpl['title'], $l['district']);
        $body = sprintf($tpl['body'], $l['district']);
        $author = $l['username'];
        $tags = [$tpl['tag'], $l['districtSlug']];
    }

    // Discussion titles are capped at 80 chars by DiscussionValidator; long
    // department/university names can overflow, so trim the descriptive part
    // and keep the "(#N)" suffix (which alone already guarantees uniqueness).
    $suffix = ' (#' . ($i + 1) . ')';
    $title = substr($title, 0, 80 - strlen($suffix)) . $suffix;

    $result = kktcCreateDiscussion([
        'title' => $title,
        'body' => $body,
        'author' => $author,
        'tags' => $tags,
        'replies' => [
            ['author' => $allUsernames[array_rand($allUsernames)], 'body' => 'Katiliyorum, ben de merak ediyordum.'],
            ['author' => $allUsernames[array_rand($allUsernames)], 'body' => 'Faydali bilgiler icin tesekkurler.'],
        ],
    ], $bus, $userIds, $tagIds, $hashtagIds);

    if ($result) {
        $likeableSeedPosts = array_merge($likeableSeedPosts, $result['posts']);
    }
}

// --- 14. Begeniler (flarum/likes) -----------------------------------------

kktcAttachRandomLikes($likeableSeedPosts, $allUsernames, $userIds);
echo count($likeableSeedPosts) . " gonderiye rastgele begeniler dagitildi.\n";

// --- 15. En iyi cevap (fof/best-answer), sadece 'universite' etiketinde --

$universiteTag = Tag::find($tagIds['universite']);
if ($universiteTag && ! $universiteTag->is_qna) {
    $universiteTag->is_qna = true;
    $universiteTag->save();
}

$bestAnswerCandidates = Discussion::whereHas('tags', function ($q) use ($tagIds) {
    $q->where('tags.id', $tagIds['universite']);
})->whereNull('best_answer_post_id')->get();

$marked = 0;
foreach ($bestAnswerCandidates as $discussion) {
    if ($marked >= 15) {
        break;
    }

    $reply = Post::where('discussion_id', $discussion->id)->where('number', '>', 1)->first();

    if (! $reply) {
        continue;
    }

    $discussion->best_answer_post_id = $reply->id;
    $discussion->best_answer_user_id = $reply->user_id;
    $discussion->best_answer_set_at = Carbon\Carbon::now();
    $discussion->save();
    $marked++;
}
echo "{$marked} universite konusuna 'en iyi cevap' isaretlendi.\n";

// --- 16. Anketler (fof/polls) ----------------------------------------------

$pollDefs = [
    ['tag' => 'gundem', 'question' => 'KKTC genelinde en cok hangi konu sizi ilgilendiriyor?', 'options' => ['Elektrik/Su', 'Ulasim', 'Egitim', 'Ekonomi']],
    ['tag' => 'universite', 'question' => 'Hangi universite kampusunu daha cok begeniyorsunuz?', 'options' => ['DAU', 'YDU', 'UKU', 'LAU', 'GAU']],
    ['tag' => 'yasam', 'question' => 'Kiralik ev ararken en cok neye dikkat edersiniz?', 'options' => ['Fiyat', 'Konum', 'Esyali olmasi', 'Ulasim']],
    ['tag' => 'serbest', 'question' => 'Hafta sonu en cok nerede vakit geciriyorsunuz?', 'options' => ['Sahilde', 'Kafede', 'Evde', 'Kampuste']],
];

$pollsCreated = 0;
foreach ($pollDefs as $i => $def) {
    $discussion = Discussion::whereHas('tags', function ($q) use ($tagIds, $def) {
        $q->where('tags.id', $tagIds[$def['tag']]);
    })->orderBy('id')->skip($i)->first();

    if (! $discussion) {
        continue;
    }

    $firstPost = Post::where('discussion_id', $discussion->id)->where('number', 1)->first();

    if (! $firstPost || Poll::where('post_id', $firstPost->id)->exists()) {
        continue;
    }

    $poll = Poll::build($def['question'], $firstPost->id, $discussion->user_id, null, true);
    $poll->save();

    foreach ($def['options'] as $answer) {
        $poll->options()->create(['answer' => $answer]);
    }

    $pollsCreated++;
}
echo "{$pollsCreated} anket olusturuldu.\n";

// --- 17. Itibar puanlari (fof/gamification) --------------------------------

$votesAssigned = 0;
foreach ($userIds as $username => $id) {
    $user = User::find($id);

    if (! $user || $user->votes > 0) {
        continue;
    }

    $user->votes = random_int(0, 250);
    $user->save();
    $votesAssigned++;
}
echo "{$votesAssigned} kullaniciya itibar puani atandi.\n";

// --- 18. Hukuki sayfalar (fof/pages) --------------------------------------
// Sayfa metinleri burada tutuluyor ki `pages` tablosu sifirlansa bile seed
// tekrar calistirildiginda yayin metinleri geri gelsin. Slug ile eslesiyor:
// var olan sayfanin basligi/icerigi guncelleniyor, yenisi ekleniyor.

$legalContactEmail = 'iletisim@kktcmeydan.com';

$legalPages = [
    [
        'slug' => 'gizlilik-politikasi',
        'title' => 'Gizlilik Politikası',
        'content' => <<<HTML
<p><strong>Son güncelleme:</strong> bu sayfa platformun güncel veri işleme uygulamalarını yansıtır ve değişiklik hâlinde yenilenir.</p>

<h2>1. Veri Sorumlusu ve Kapsam</h2>
<p>KKTC Meydan ("Platform"), Kuzey Kıbrıs Türk Cumhuriyeti'nde faaliyet gösteren bir topluluk ve tartışma platformudur. Platform, işlediği kişisel veriler bakımından <strong>veri sorumlusu</strong> sıfatını taşır. Kişisel veriler; KKTC'de yürürlükte olan kişisel verilerin korunması mevzuatı ile Türkiye Cumhuriyeti'nin 6698 sayılı Kişisel Verilerin Korunması Kanunu (KVKK) ilkeleri esas alınarak işlenir.</p>
<p>Verileriniz <strong>hukuka ve dürüstlük kurallarına uygun</strong>, <strong>belirli, açık ve meşru amaçlarla</strong>, <strong>işlendikleri amaçla bağlantılı, sınırlı ve ölçülü</strong> biçimde ve <strong>gerektiği süre kadar</strong> saklanır.</p>

<h2>2. İşlenen Veri Kategorileri</h2>
<ul>
  <li><strong>Kimlik ve iletişim verileri:</strong> kullanıcı adı, e-posta adresi, isteğe bağlı profil bilgileri (avatar, biyografi).</li>
  <li><strong>İşlem güvenliği verileri:</strong> IP adresi, oturum ve giriş kayıtları (log), tarayıcı/cihaz bilgisi, şifrenizin geri döndürülemez şekilde şifrelenmiş (hash) hâli. Açık şifreniz hiçbir aşamada saklanmaz.</li>
  <li><strong>İşletme ve ilan verileri:</strong> yalnızca <em>sizin kendi rızanızla</em> girdiğiniz işletme adresi, telefon, WhatsApp numarası, çalışma saatleri ile ilan içeriğindeki konum, fiyat ve iletişim bilgileri. Bu alanlar profilinizde/ilanınızda <strong>herkese açık</strong> yayımlanır; paylaşmak zorunda değilsiniz.</li>
  <li><strong>Kullanıcı içeriği:</strong> açtığınız konular, gönderiler, yorumlar, beğeniler, anket oyları, etkinlik katılım (RSVP) kayıtları ve sorun bildirimleri.</li>
</ul>

<h2>3. Anonim Paylaşım ve Kimlik Gizliliği</h2>
<p>Anonim paylaşım özelliğiyle açılan konu ve gönderilerde <strong>gerçek kullanıcı kimliği ve IP adresi sunucu tarafında maskelenir</strong>; yazarın adı diğer kullanıcılara, arama sonuçlarına ve API yanıtlarına hiçbir şekilde yansıtılmaz. Gizleme istemci (tarayıcı) tarafında değil, sunucu tarafında uygulanır.</p>
<p>Anonim içeriğin gerçek yazarına yalnızca şu hâllerde erişilebilir:</p>
<ul>
  <li>ilgili yetkiye sahip <strong>moderatörler</strong>, kötüye kullanım, taciz veya suç teşkil eden içeriğin incelenmesi amacıyla;</li>
  <li><strong>yetkili kamu makamlarının</strong> KKTC mevzuatı uyarınca usulüne uygun, yazılı ve yasal dayanağı bulunan talebi hâlinde.</li>
</ul>
<p>Bunun dışında anonim kimlik bilgisi hiçbir kişiye, kuruma veya üçüncü tarafa açıklanmaz.</p>

<h2>4. İşleme Amaçları ve Hukuki Sebepler</h2>
<ul>
  <li><strong>Sözleşmenin ifası:</strong> üyelik hesabının oluşturulması ve yönetimi, içeriklerin yayımlanması, bildirim ve hatırlatmaların iletilmesi.</li>
  <li><strong>Hukuki yükümlülük:</strong> yetkili makamların yasal taleplerinin karşılanması ve mevzuat gereği tutulması gereken kayıtların saklanması.</li>
  <li><strong>Meşru menfaat:</strong> spam, sahte hesap, dolandırıcılık ve kötüye kullanımın önlenmesi; platform güvenliğinin ve teknik sürekliliğinin sağlanması; topluluk kurallarının uygulanması.</li>
  <li><strong>Açık rıza:</strong> işletme iletişim bilgileri, ilan detayları gibi tamamen isteğe bağlı olarak paylaştığınız veriler.</li>
</ul>
<p>Kişisel verileriniz <strong>pazarlama amacıyla üçüncü kişilere satılmaz, kiralanmaz veya devredilmez.</strong></p>

<h2>5. Çerezler (Cookies)</h2>
<p>Platform yalnızca <strong>zorunlu teknik çerezler</strong> kullanır: oturumunuzun açık kalmasını sağlayan oturum çerezi, güvenlik (CSRF) çerezi ve tema/dil gibi arayüz tercihlerinizi hatırlayan tercih çerezi. Reklam takibi veya profilleme amaçlı üçüncü taraf çerezi kullanılmaz.</p>
<p>Çerezleri tarayıcı ayarlarınızdan silebilir veya engelleyebilirsiniz; bu durumda oturum açma başta olmak üzere bazı işlevler çalışmaz.</p>

<h2>6. Saklama Süresi</h2>
<p>Hesabınız aktif olduğu sürece hesap ve içerik verileriniz saklanır. Güvenlik amaçlı erişim kayıtları (log) makul bir süre tutulduktan sonra silinir. Hesabınızı kapattığınızda kimlik ve profil verileriniz kaldırılır; tartışmaların bütünlüğünün korunması amacıyla gönderileriniz <strong>anonimleştirilerek</strong> yayında bırakılabilir.</p>

<h2>7. Veri Güvenliği</h2>
<p>Şifreler geri döndürülemez biçimde saklanır, yönetim işlemleri yetki denetimine tabidir, sunucu ve uygulama katmanı düzenli olarak güncellenir. Buna karşın internet üzerinden yapılan hiçbir aktarımın mutlak güvenlikte olduğu taahhüt edilemez.</p>

<h2>8. Haklarınız</h2>
<p>Kişisel verilerinizle ilgili olarak şu haklara sahipsiniz:</p>
<ul>
  <li>işlenip işlenmediğini öğrenme ve <strong>verilerinize erişme</strong>;</li>
  <li>eksik veya yanlış işlenmiş verilerin <strong>düzeltilmesini</strong> isteme;</li>
  <li>verilerinizin <strong>silinmesini veya anonimleştirilmesini</strong> talep etme;</li>
  <li>işlemeye <strong>itiraz etme</strong> ve verilerinizin bir kopyasını isteme.</li>
</ul>
<p>Taleplerinizi <strong>{$legalContactEmail}</strong> adresine iletebilirsiniz. Başvurularınız en kısa sürede, her hâlükârda makul bir süre içinde yanıtlanır. Kimliğinizin doğrulanması amacıyla talebin hesabınıza kayıtlı e-posta adresinden gönderilmesi gerekir.</p>

<h2>9. Değişiklikler</h2>
<p>Bu politika güncellenebilir. Esaslı değişiklikler platform üzerinden duyurulur ve bu sayfadaki güncelleme bilgisi yenilenir.</p>

<h2>10. İletişim</h2>
<p>Gizlilikle ilgili tüm soru, talep ve şikâyetleriniz için: <strong>{$legalContactEmail}</strong></p>
HTML,
    ],
    [
        'slug' => 'kullanim-sartlari',
        'title' => 'Kullanım Şartları',
        'content' => <<<HTML
<p>KKTC Meydan'a ("Platform") kayıt olarak, giriş yaparak veya içerik paylaşarak aşağıdaki şartları kabul etmiş sayılırsınız. Şartları kabul etmiyorsanız platformu kullanmamalısınız.</p>

<h2>1. Üyelik ve Hesap Güvenliği</h2>
<ul>
  <li>Hesap açarken <strong>doğru ve güncel bilgi</strong> beyan etmeniz gerekir; başkasının kimliği, işletme adı veya iletişim bilgisiyle hesap açılamaz.</li>
  <li>Şifrenizin gizliliğinden ve hesabınız üzerinden gerçekleştirilen tüm işlemlerden <strong>siz sorumlusunuz</strong>. Güçlü bir şifre kullanın ve başkasıyla paylaşmayın.</li>
  <li>Yetkisiz bir erişim veya şüpheli bir hareket fark ederseniz derhal <strong>{$legalContactEmail}</strong> adresine bildirin.</li>
  <li>Yaptırımdan kaçınmak amacıyla açılan çoklu hesaplar ve otomatik (bot) kayıtlar kapatılır.</li>
</ul>

<h2>2. Kullanıcı Üretimi İçerik (UGC) ve Yasaklar</h2>
<p>Paylaştığınız her içeriğin hukuki sorumluluğu size aittir. Aşağıdaki içerikler kesinlikle yasaktır:</p>
<ul>
  <li><strong>Hakaret, sövme, tehdit ve taciz</strong>; kişi veya topluluklara yönelik yıldırma amaçlı paylaşımlar.</li>
  <li><strong>Nefret söylemi</strong>: etnik köken, dil, din, cinsiyet, cinsel yönelim, engellilik veya uyruk temelli aşağılama ve ayrımcılık.</li>
  <li><strong>Kişisel veri ifşası (doxxing)</strong>: bir kişinin adresi, telefonu, kimlik numarası, iş yeri, aile bilgileri veya özel yazışmalarının rızası olmadan yayımlanması.</li>
  <li><strong>Sahte, yanıltıcı veya dolandırıcılık amaçlı ilanlar</strong>; var olmayan konut/araç/iş ilanları, kapora tuzakları, ödeme yönlendirmeleri.</li>
  <li><strong>Kurum veya kişi hedefli karalama</strong>: doğruluğu ortaya konmamış isnatlarla itibar zedeleyici kampanya yürütülmesi.</li>
  <li>Yasa dışı ürün/hizmet tanıtımı, telif hakkı ihlali, müstehcen veya şiddet içerikli materyal, spam ve izinsiz reklam, oy/etkileşim manipülasyonu.</li>
</ul>

<h2>3. Otomatik ve Manuel Moderasyon</h2>
<p>Platform, topluluk güvenliği için otomatik filtreler ve insan moderasyonu birlikte kullanır. Özellikle <strong>sağlık</strong>, <strong>güvenlik/acil durum</strong> ve <strong>kamu hizmetleri</strong> konularındaki paylaşımlar, yanlış bilginin doğrudan zarar doğurma riski nedeniyle yayımlanmadan önce veya sonra denetimden geçebilir; onay kuyruğuna alınabilir, düzenlenebilir veya kaldırılabilir.</p>
<p>Kuralları ihlal eden içerikler uyarı yapılmaksızın kaldırılabilir; tekrarlanan ihlallerde hesap askıya alınabilir veya kapatılabilir. Moderasyon kararlarına ilişkin itirazlarınızı iletişim adresinden iletebilirsiniz.</p>

<h2>4. İlan ve İşletme Dizini — Sorumluluk Sınırı</h2>
<ul>
  <li>İlanlarda ve işletme profillerinde yer alan fiyat, konum, nitelik ve iletişim bilgileri <strong>tamamen ilanı veren kullanıcıya aittir</strong>; Platform bu bilgilerin doğruluğunu, güncelliğini veya ilanın gerçekliğini garanti etmez.</li>
  <li>Platform, alıcı ile satıcı, kiracı ile kiraya veren, işveren ile iş arayan arasındaki <strong>ticari ilişkiye taraf değildir</strong>; aracılık, komisyonculuk veya emlakçılık hizmeti sunmaz.</li>
  <li>Ödeme, kapora, sözleşme ve teslimat süreçleri taraflar arasında yürür. Peşin ödeme taleplerine karşı dikkatli olun, mümkün olduğunca yüz yüze görün ve resmi belge isteyin.</li>
  <li>İlan paylaşırken doğru kategori ve etiketi (#satılık, #kiralık, #iş-ilanı, #ev-arkadaşı, #ikinci-el) kullanın; konum, fiyat ve iletişim bilgisini açıkça belirtin.</li>
</ul>

<h2>5. Fikri Mülkiyet ve Lisans</h2>
<p>Paylaştığınız metin, görsel ve diğer içeriklerin <strong>telif hakları sizde kalır</strong>. Platformda yayımlamakla; içeriğin platform üzerinde saklanması, görüntülenmesi, çoğaltılması, arama motorlarına ve site içi arama sonuçlarına açılması için Platform'a <strong>münhasır olmayan, dünya çapında, süresiz ve ücretsiz bir kullanım lisansı</strong> vermiş olursunuz. Bu lisans, içeriğinizin size ait olmaktan çıktığı anlamına gelmez.</p>
<p>Platform'un adı, logosu, arayüz tasarımı ve yazılımı üzerindeki haklar saklıdır. Size ait olmayan içeriği izinsiz yayımlamayın; telif ihlali bildirimlerinizi iletişim adresine iletebilirsiniz.</p>

<h2>6. Sorumluluğun Sınırlandırılması ve Uygulanacak Hukuk</h2>
<p>Platform <strong>"olduğu gibi" (as is)</strong> sunulur. Kesintisiz, hatasız veya belirli bir amaca uygun çalışacağı taahhüt edilmez; bakım, teknik arıza, saldırı veya güncelleme nedeniyle hizmete ara verilebilir. Platform, kullanıcı içeriğinden, kullanıcılar arasındaki uyuşmazlıklardan ve içeriğe güvenilerek alınan kararlardan doğan <strong>doğrudan veya dolaylı zararlardan sorumlu tutulamaz</strong>.</p>
<p>Bu şartlar <strong>Kuzey Kıbrıs Türk Cumhuriyeti hukukuna</strong> tabidir. Şartların uygulanmasından doğabilecek her türlü ihtilafın çözümünde <strong>KKTC Lefkoşa Kaza Mahkemeleri</strong> yetkilidir.</p>

<h2>7. Şartlardaki Değişiklikler ve İletişim</h2>
<p>Bu şartlar zaman zaman güncellenebilir; güncelleme sonrasında platformu kullanmaya devam etmeniz yeni şartları kabul ettiğiniz anlamına gelir. Soru, bildirim ve itirazlarınız için: <strong>{$legalContactEmail}</strong></p>
<p>Kişisel verilerinizin işlenmesine ilişkin ayrıntılar için <a href="/p/gizlilik-politikasi">Gizlilik Politikası</a> sayfasını inceleyin.</p>
HTML,
    ],
];

// Sayfalar `pages` tablosuna dogrudan INSERT ile DEGIL, FoF\Pages\Page modeli
// uzerinden yaziliyor. Sebep: model `content`i setter'da s9e TextFormatter ile
// parse edip saklıyor, okurken `unparse` ediyor (bkz. vendor/fof/pages/src/
// Page.php). Ham HTML dogrudan sutuna yazilirsa unparse onu parse edilmis
// sanip tum etiketleri soyuyor ve sayfa duz metin olarak yayina cikiyor -
// gozlemlenen tam olarak buydu. Model uzerinden yazinca parse/unparse gidis
// donusu kayipsiz oluyor ve `is_html = 1` ile HTML aynen render ediliyor.
if (class_exists(\FoF\Pages\Page::class) && $db->getSchemaBuilder()->hasTable('pages')) {
    foreach ($legalPages as $pageData) {
        $page = \FoF\Pages\Page::where('slug', $pageData['slug'])->first();
        $isNew = ! $page;

        if ($isNew) {
            $page = new \FoF\Pages\Page();
            $page->slug = $pageData['slug'];
            $page->time = Carbon\Carbon::now();
        } else {
            $page->edit_time = Carbon\Carbon::now();
        }

        $page->title = $pageData['title'];
        $page->content = $pageData['content'];
        $page->is_html = true;
        $page->is_hidden = false;
        $page->is_restricted = false;
        $page->save();

        echo $isNew
            ? "Hukuki sayfa olusturuldu: /p/{$pageData['slug']}\n"
            : "Hukuki sayfa guncellendi: /p/{$pageData['slug']}\n";
    }
} else {
    echo "fof/pages kurulu degil, hukuki sayfalar atlandi.\n";
}

// --- 19. Kayit ekrani zorunlu kabul checkbox'lari (fof/terms) --------------
// `url` alani kayit formundaki linki belirliyor; ayni url ile eslesen kayit
// varsa adi/sirasi guncelleniyor, yoksa olusturuluyor.

$termsPolicies = [
    ['name' => 'Kullanım Şartları', 'url' => '/p/kullanim-sartlari', 'sort' => 1],
    ['name' => 'Gizlilik Politikası', 'url' => '/p/gizlilik-politikasi', 'sort' => 2],
];

if ($db->getSchemaBuilder()->hasTable('fof_terms_policies')) {
    $now = Carbon\Carbon::now();

    foreach ($termsPolicies as $policy) {
        $existing = $db->table('fof_terms_policies')->where('url', $policy['url'])->first();

        if ($existing) {
            $db->table('fof_terms_policies')->where('id', $existing->id)->update([
                'name' => $policy['name'],
                'sort' => $policy['sort'],
                'optional' => 0,
                'updated_at' => $now,
            ]);
            echo "Kayit sarti guncellendi: {$policy['name']}\n";
        } else {
            $db->table('fof_terms_policies')->insert([
                'name' => $policy['name'],
                'url' => $policy['url'],
                'sort' => $policy['sort'],
                'optional' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            echo "Kayit sarti olusturuldu: {$policy['name']}\n";
        }
    }
} else {
    echo "fof/terms kurulu degil, kayit sartlari atlandi.\n";
}

// --- 20. Ornek reklam banner'lari (ads-manager) ---------------------------
// Gorseller repo icindeki public/assets/ads/*.svg dosyalari; URL'ler
// config.php'deki forum adresinden turetiliyor ki AdUrlValidator'in
// bekledigi mutlak http(s) formatinda olsunlar. Baslik ile eslesiyor:
// istatistik sutunlari (impressions/clicks) korunuyor.

$flarumConfig = require __DIR__ . '/config.php';
$baseUrl = rtrim($flarumConfig['url'] ?? 'http://localhost:8080', '/');

$seedAds = [
    [
        'title' => 'Ercan Havalimanı Ulaşım ve Havaş Rehberi 2026',
        'image_url' => $baseUrl . '/assets/ads/ercan-ulasim.svg',
        'target_url' => $baseUrl . '/t/ulasim',
        'target_category_slug' => 'ulasim',
    ],
    [
        'title' => 'KKTC Öğrenci Evleri & Yurt Rehberi',
        'image_url' => $baseUrl . '/assets/ads/ogrenci-evleri.svg',
        'target_url' => $baseUrl . '/t/universite',
        'target_category_slug' => 'universite',
    ],
    [
        'title' => 'Girne ve Lefkoşa Yerel İşletme Dizini',
        'image_url' => $baseUrl . '/assets/ads/yerel-isletme-dizini.svg',
        'target_url' => $baseUrl . '/t/bolgeler',
        'target_category_slug' => 'bolgeler',
    ],
];

if ($db->getSchemaBuilder()->hasTable('ads')) {
    $now = Carbon\Carbon::now();

    foreach ($seedAds as $ad) {
        $existing = $db->table('ads')->where('title', $ad['title'])->first();

        if ($existing) {
            $db->table('ads')->where('id', $existing->id)->update([
                'image_url' => $ad['image_url'],
                'target_url' => $ad['target_url'],
                'target_category_slug' => $ad['target_category_slug'],
                'is_active' => 1,
                'updated_at' => $now,
            ]);
            echo "Reklam guncellendi: {$ad['title']}\n";
        } else {
            $db->table('ads')->insert([
                'title' => $ad['title'],
                'image_url' => $ad['image_url'],
                'target_url' => $ad['target_url'],
                'target_category_slug' => $ad['target_category_slug'],
                'target_district_slug' => null,
                'target_university_slug' => null,
                'is_active' => 1,
                'impressions_count' => 0,
                'clicks_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            echo "Reklam olusturuldu: {$ad['title']}\n";
        }
    }
} else {
    echo "ads-manager kurulu degil, ornek reklamlar atlandi.\n";
}

echo "Seed tamamlandi. Toplam kullanici: " . count($userIds) . ', toplam konu: ' . Discussion::count() . "\n";
