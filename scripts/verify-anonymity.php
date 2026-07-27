<?php

/*
 * KKTC Meydan - Anonimlik sizinti dogrulama betigi (Faz 1).
 *
 * Her senaryoyu GERCEK HTTP uzerinden, GIRIS YAPMAMIS (guest) bir istemci
 * olarak calistirir - yani tum middleware/serializer zinciri devrede. Amac
 * tek soruyu cevaplamak: anonim bir gonderinin yazarinin kullanici adi veya
 * id'si API yanitinda herhangi bir yerde goruluyor mu?
 *
 * Kullanim (uygulama konteyneri icinde):
 *   docker compose exec flarum-app php scripts/verify-anonymity.php
 */

require __DIR__.'/../vendor/autoload.php';

use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\User\User;

$site = require __DIR__.'/../site.php';
$app = $site->bootApp();

$baseUrl = getenv('VERIFY_BASE_URL') ?: 'http://flarum-web';

// --- Fikstur: anonim bir konu ve gercek yazari ---------------------------

$discussion = Discussion::where('is_anonymous', true)->orderBy('id')->first();

if (! $discussion) {
    fwrite(STDERR, "Anonim konu bulunamadi. Once `php seed.php` calistirin.\n");
    exit(1);
}

$author = User::find($discussion->user_id);
$firstPost = Post::where('discussion_id', $discussion->id)->where('number', 1)->first();

if (! $author || ! $firstPost) {
    fwrite(STDERR, "Fikstur eksik (yazar veya ilk gonderi yok).\n");
    exit(1);
}

$authorName = $author->username;
$authorId = (string) $author->id;

echo "Fikstur: konu #{$discussion->id} \"{$discussion->title}\"\n";
echo "         gercek yazar: {$authorName} (id {$authorId})\n";
echo "         ilk gonderi #{$firstPost->id} is_anonymous=".($firstPost->is_anonymous ? '1' : '0')."\n";
echo "         hedef: {$baseUrl}\n\n";

// --- Yardimcilar ---------------------------------------------------------

function apiGet(string $baseUrl, string $path): array
{
    $ch = curl_init($baseUrl.$path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Accept: application/vnd.api+json'],
        CURLOPT_TIMEOUT => 20,
    ]);

    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        throw new RuntimeException("curl hatasi: {$err}");
    }

    return ['status' => $status, 'body' => (string) $body, 'json' => json_decode((string) $body, true)];
}

/** Yanitin `included` bloğunda bu kullanici id'siyle bir `users` kaydi var mi? */
function includesUser(?array $json, string $userId): bool
{
    foreach ($json['included'] ?? [] as $resource) {
        if (($resource['type'] ?? null) === 'users' && (string) ($resource['id'] ?? '') === $userId) {
            return true;
        }
    }

    return false;
}

/** Ana veri kumesinde verilen tip/id var mi? */
function containsResource(?array $json, string $type, string $id): bool
{
    $data = $json['data'] ?? [];
    $data = isset($data['type']) ? [$data] : $data;

    foreach ($data as $resource) {
        if (($resource['type'] ?? null) === $type && (string) ($resource['id'] ?? '') === $id) {
            return true;
        }
    }

    return false;
}

$results = [];

function check(string $name, bool $passed, string $detail = ''): void
{
    global $results;

    $results[] = ['name' => $name, 'passed' => $passed, 'detail' => $detail];

    printf("[%s] %s%s\n", $passed ? ' GECTI ' : 'KALDI!!', $name, $detail !== '' ? "\n          {$detail}" : '');
}

// --- 1. filter[author] ile post deanonimizasyonu -------------------------

$r = apiGet($baseUrl, '/api/posts?filter[author]='.rawurlencode($authorName).'&filter[type]=comment');
check(
    'GET /api/posts?filter[author]=X anonim gonderiyi dondurmuyor',
    ! containsResource($r['json'], 'posts', (string) $firstPost->id),
    "HTTP {$r['status']}, donen post sayisi: ".count($r['json']['data'] ?? [])
);

// --- 2. filter[author] ile discussion deanonimizasyonu -------------------

$r = apiGet($baseUrl, '/api/discussions?filter[author]='.rawurlencode($authorName));
check(
    'GET /api/discussions?filter[author]=X anonim konuyu dondurmuyor',
    ! containsResource($r['json'], 'discussions', (string) $discussion->id),
    "HTTP {$r['status']}, donen konu sayisi: ".count($r['json']['data'] ?? [])
);

// --- 3. Arama gambit'i: filter[q]=author:X -------------------------------

$r = apiGet($baseUrl, '/api/discussions?filter[q]='.rawurlencode('author:'.$authorName));
check(
    'GET /api/discussions?filter[q]=author:X anonim konuyu dondurmuyor',
    ! containsResource($r['json'], 'discussions', (string) $discussion->id),
    "HTTP {$r['status']}, donen konu sayisi: ".count($r['json']['data'] ?? [])
);

// --- 4. Serbest metin aramasi: mostRelevantPost.user sizintisi -----------

$keyword = preg_split('/\s+/', trim($discussion->title))[0] ?? '';
$r = apiGet($baseUrl, '/api/discussions?filter[q]='.rawurlencode($keyword));
check(
    'Arama sonuclarinda mostRelevantPost.user yazari sizdirmiyor',
    ! includesUser($r['json'], $authorId),
    "HTTP {$r['status']}, arama terimi: \"{$keyword}\""
);

// --- 5. Konu detayi: ShowDiscussion ------------------------------------

$r = apiGet($baseUrl, '/api/discussions/'.$discussion->id);
check(
    'GET /api/discussions/{id} yazari sizdirmiyor',
    ! includesUser($r['json'], $authorId),
    "HTTP {$r['status']}"
);

// --- 6. Genel konu listesi ---------------------------------------------

$r = apiGet($baseUrl, '/api/discussions');
check(
    'GET /api/discussions (genel liste) yazari sizdirmiyor',
    ! includesUser($r['json'], $authorId),
    "HTTP {$r['status']}"
);

// --- 7. Konudaki gonderi akisi -----------------------------------------

$r = apiGet($baseUrl, '/api/posts?filter[discussion]='.$discussion->id);
check(
    'GET /api/posts?filter[discussion]=N yazari sizdirmiyor',
    ! includesUser($r['json'], $authorId),
    "HTTP {$r['status']}"
);

// --- 8. Ham gövde taramasi: kullanici adi hicbir yanitta gecmemeli -------

$paths = [
    '/api/discussions',
    '/api/discussions/'.$discussion->id,
    '/api/posts?filter[discussion]='.$discussion->id,
    '/api/discussions?filter[q]='.rawurlencode($keyword),
];

$leakedIn = [];

foreach ($paths as $path) {
    $r = apiGet($baseUrl, $path);

    if (stripos($r['body'], $authorName) !== false) {
        $leakedIn[] = $path;
    }
}

check(
    'Yazarin kullanici adi hicbir yanit govdesinde gecmiyor',
    $leakedIn === [],
    $leakedIn ? 'Sizinti: '.implode(', ', $leakedIn) : 'Taranan uc nokta: '.count($paths)
);

// --- Ozet ---------------------------------------------------------------

$failed = array_filter($results, fn ($r) => ! $r['passed']);

echo "\n".str_repeat('-', 60)."\n";
printf("%d/%d kontrol gecti.\n", count($results) - count($failed), count($results));

if ($failed) {
    echo "\nBASARISIZ:\n";
    foreach ($failed as $f) {
        echo "  - {$f['name']}\n";
    }
    exit(1);
}

echo "Anonimlik sizintisi tespit edilmedi.\n";
exit(0);
