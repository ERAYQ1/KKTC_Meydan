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
    'gundem' => [
        'title' => 'Bugun KKTC genelinde neler oluyor?',
        'body' => "Gundemi burada takip edelim. Onemli bir haber gordugunuzde paylasin, kaynak eklemeyi unutmayin.",
        'author' => 'ada_lefkosa',
        'reply' => 'Ercan havalimaninda bugun yogunluk varmis, yola cikacaklar erken gitsin.',
        'replyAuthor' => 'mehmet_girne',
    ],
    'universite' => [
        'title' => 'DAU ve YDU icin donem basi ders notu paylasim konusu',
        'body' => "Ders notlariniz, gecmis sinav sorulariniz varsa buraya birakabilirsiniz. Bolumunuzu belirtmeyi unutmayin.",
        'author' => 'zeynep_dau',
        'reply' => 'Bilgisayar muhendisligi 2. sinif icin veri yapilari notlarim var, paylasirim.',
        'replyAuthor' => 'can_maguza',
    ],
    'emlak' => [
        'title' => 'Girne merkezde ogrenciye uygun kiralik daire arayanlar',
        'body' => "Girne merkeze yakin, esyali, tek+bir veya iki+bir daire arayanlar burada bilgi paylassin.",
        'author' => 'mehmet_girne',
        'reply' => 'Karakum bolgesinde uygun fiyatli birkac secenek gordum, ilan linkini atarim.',
        'replyAuthor' => 'ada_lefkosa',
    ],
    'ikinci-el' => [
        'title' => 'Donem sonu tasinacaklar icin ikinci el esya ilanlari',
        'body' => "Okul bitince ya da yurt degistirirken elden cikaracaginiz esyalari buraya yazabilirsiniz.",
        'author' => 'can_maguza',
        'reply' => 'Az kullanilmis mini buzdolabi ve masa satiyorum, Magusa icinde teslim ederim.',
        'replyAuthor' => 'zeynep_dau',
    ],
    'ulasim' => [
        'title' => 'Ercan - sehir merkezi otobus ve minibus saatleri',
        'body' => "Guncel sefer saatlerini ve fiyatlarini burada paylasip guncel tutalim.",
        'author' => 'zeynep_dau',
        'reply' => 'Lefkosa - Ercan hatti sabah 06:00dan itibaren saat basi kalkiyor.',
        'replyAuthor' => 'mehmet_girne',
    ],
    'serbest' => [
        'title' => 'KKTCde en sevdiginiz kahvalti / kahve mekanlari?',
        'body' => "Yerel onerilerinizi paylasin, yeni gelenler icin faydali olur.",
        'author' => 'ada_lefkosa',
        'reply' => 'Girne limaninda sahil kenarindaki kucuk kahvaltici cok iyi, tavsiye ederim.',
        'replyAuthor' => 'can_maguza',
    ],
];

foreach ($seedThreads as $slug => $thread) {
    $existing = \Flarum\Discussion\Discussion::where('title', $thread['title'])->first();

    if ($existing) {
        echo "Konu zaten var, atlaniyor: {$thread['title']}\n";
        continue;
    }

    $author = User::find($userIds[$thread['author']]);

    $discussion = $bus->dispatch(new StartDiscussion($author, [
        'attributes' => [
            'title' => $thread['title'],
            'content' => $thread['body'],
        ],
        'relationships' => [
            'tags' => [
                'data' => [
                    ['type' => 'tags', 'id' => (string) $tagIds[$slug]],
                ],
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
