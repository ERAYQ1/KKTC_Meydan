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
 */

require __DIR__ . '/vendor/autoload.php';

use Flarum\Discussion\Command\StartDiscussion;
use Flarum\Group\Group;
use Flarum\Post\Command\PostReply;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Command\CreateTag;
use Flarum\Tags\Tag;
use Flarum\User\User;

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

$themeLessPath = __DIR__ . '/assets/theme.less';
if (file_exists($themeLessPath)) {
    $settings->set('custom_less', file_get_contents($themeLessPath));
}

echo "Site ayarlari uygulandi: {$config['site_name']}\n";

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

// --- 3a. Retired categories: migrate discussions, then delete the old tag --

$retiredDiscussionIds = [];

foreach ($config['retired_categories'] ?? [] as $retired) {
    // Only match an actual (not-yet-retired) primary category tag - a hashtag
    // that happens to share the same slug (e.g. #ikinci-el) must never be
    // mistaken for the legacy category and re-deleted on every run.
    $oldTag = Tag::where('slug', $retired['slug'])->whereNotNull('position')->first();

    if (! $oldTag) {
        continue;
    }

    $newTagId = $tagIds[$retired['migrate_to']] ?? null;
    $discussionIds = Tag::query()->getConnection()->table('discussion_tag')
        ->where('tag_id', $oldTag->id)
        ->pluck('discussion_id');

    $retiredDiscussionIds[$retired['slug']] = $discussionIds;

    if ($newTagId) {
        foreach ($discussionIds as $discussionId) {
            $exists = Tag::query()->getConnection()->table('discussion_tag')
                ->where('discussion_id', $discussionId)
                ->where('tag_id', $newTagId)
                ->exists();

            if (! $exists) {
                Tag::query()->getConnection()->table('discussion_tag')->insert([
                    'discussion_id' => $discussionId,
                    'tag_id' => $newTagId,
                ]);
            }
        }

        echo "Emekli kategori tasindi: {$retired['slug']} -> {$retired['migrate_to']} ({$discussionIds->count()} konu)\n";
    }

    Tag::query()->getConnection()->table('discussion_tag')->where('tag_id', $oldTag->id)->delete();
    $oldTag->delete();
    echo "Emekli kategori silindi: {$retired['slug']}\n";
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

foreach ($config['retired_categories'] ?? [] as $retired) {
    foreach ($retired['add_hashtags'] ?? [] as $hashtagSlug) {
        $hashtagId = $hashtagIds[$hashtagSlug] ?? null;

        if (! $hashtagId) {
            continue;
        }

        foreach ($retiredDiscussionIds[$retired['slug']] ?? [] as $discussionId) {
            $exists = Tag::query()->getConnection()->table('discussion_tag')
                ->where('discussion_id', $discussionId)
                ->where('tag_id', $hashtagId)
                ->exists();

            if (! $exists) {
                Tag::query()->getConnection()->table('discussion_tag')->insert([
                    'discussion_id' => $discussionId,
                    'tag_id' => $hashtagId,
                ]);
            }
        }
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
    ['username' => 'can_maguza', 'email' => 'can.maguza@example.kktcmeydan.test', 'role' => 'İşletme'],
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
        'hashtags' => ['girne', 'bildirildi'],
        'title' => 'Alsancak sahil yolunda buyuk bir cukur var',
        'body' => "Konum: Alsancak sahil yolu, eczane karsisi. Fotograf: (eklenecek). Iki gundur duruyor, arac lastigine zarar verebilir.",
        'author' => 'mehmet_girne',
        'reply' => 'Ben de gordum, gece karanlikta fark edilmiyor, tehlikeli.',
        'replyAuthor' => 'ada_lefkosa',
    ],
    [
        'tag' => 'sorun-bildir',
        'hashtags' => ['lefkosa', 'inceleniyor'],
        'title' => 'Gonyeli meydaninda sokak lambalari bir haftadir yanmiyor',
        'body' => "Konum: Gonyeli meydani ve cevresi. Aksam saatlerinde yayalar icin guvenlik riski olusturuyor.",
        'author' => 'ada_lefkosa',
        'reply' => 'Belediyeye bildirdim, ekip gonderileceği soylendi, takipteyim.',
        'replyAuthor' => 'zeynep_dau',
    ],
    [
        'tag' => 'sorun-bildir',
        'hashtags' => ['gazimagusa', 'cozuldu'],
        'title' => 'Sakarya bolgesinde 3 gundur su kesintisi vardi',
        'body' => "Konum: Sakarya, Gazimagusa. Bildirimden 3 gun sonra su verildi, cozuldu olarak isaretliyorum.",
        'author' => 'can_maguza',
        'reply' => 'Bizim sokakta da dun aksam su geldi, tesekkurler bilgi icin.',
        'replyAuthor' => 'hasan_karpaz',
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

    $replyAuthor = User::find($userIds[$thread['replyAuthor']]);

    $bus->dispatch(new PostReply(
        $discussion->id,
        $replyAuthor,
        ['attributes' => ['content' => $thread['reply']]],
        '127.0.0.1'
    ));

    echo "Konu olusturuldu: {$thread['title']} (#{$discussion->id})\n";
}

echo "Seed tamamlandi.\n";
