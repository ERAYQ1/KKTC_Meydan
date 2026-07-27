<?php

/*
 * Faz 3 guvenlik sertlestirme - tek seferlik dogrulama betigi. Gercek HTTP
 * istekleriyle: ads-manager throttle + URL validasyonu + business-profile
 * grup gate'i kontrol eder. Kullanim:
 *   docker compose exec flarum-app php scripts/verify-faz3.php
 */

require __DIR__.'/../vendor/autoload.php';

use Flarum\Http\DeveloperAccessToken;
use Flarum\User\User;
use KktcMeydan\AdsManager\Ad;

$site = require __DIR__.'/../site.php';
$app = $site->bootApp();

$baseUrl = getenv('VERIFY_BASE_URL') ?: 'http://flarum-web';

function httpJson(string $method, string $url, ?string $token = null, array $body = null): array
{
    $ch = curl_init($url);
    $headers = ['Content-Type: application/vnd.api+json'];
    if ($token) {
        $headers[] = "Authorization: Token $token";
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$status, $raw];
}

$fail = 0;

// --- 1. Ads: URL validation -------------------------------------------
$admin = User::where('is_email_confirmed', true)->whereHas('groups', function ($q) {
    $q->where('group_id', 1);
})->first() ?? User::find(1);
$adminToken = DeveloperAccessToken::generate($admin->id);
$adminToken->save();
$token = $adminToken->token;

[$status, $raw] = httpJson('POST', "$baseUrl/api/ads", $token, [
    'data' => ['attributes' => [
        'title' => 'test-ad-xss', 'imageUrl' => 'javascript:alert(1)', 'targetUrl' => 'https://example.com',
    ]],
]);
echo ($status === 422 ? "[OK]" : "[FAIL ($status)]")." malicious imageUrl scheme rejected\n";
if ($status !== 422) {
    $fail++;
    echo "  body: $raw\n";
}

[$status, $raw] = httpJson('POST', "$baseUrl/api/ads", $token, [
    'data' => ['attributes' => [
        'title' => 'test-ad-ok', 'imageUrl' => 'https://example.com/a.png', 'targetUrl' => 'https://example.com',
    ]],
]);
echo ($status === 201 ? "[OK]" : "[FAIL ($status)]")." valid https URLs accepted\n";
$adId = null;
if ($status === 201) {
    $decoded = json_decode($raw, true);
    $adId = $decoded['data']['id'] ?? null;
} else {
    $fail++;
    echo "  body: $raw\n";
}

// --- 2. Ads: findOrFail on unknown id -----------------------------------
// Authorization header used to skip CSRF (a real forum JS client sends the
// CSRF cookie+header instead; the throttle/findOrFail logic under test
// doesn't care which auth path got it past that check).
[$status, ] = httpJson('POST', "$baseUrl/api/ads/999999/impression", $token);
echo ($status === 404 ? "[OK]" : "[FAIL ($status)]")." impression on unknown ad -> 404\n";
if ($status !== 404) {
    $fail++;
}

// --- 3. Ads: throttle ----------------------------------------------------
if ($adId) {
    [$s1, ] = httpJson('POST', "$baseUrl/api/ads/$adId/impression", $token);
    [$s2, ] = httpJson('POST', "$baseUrl/api/ads/$adId/impression", $token);
    echo ($s1 === 204 && $s2 === 429 ? "[OK]" : "[FAIL ($s1, $s2)]")." impression throttled on 2nd rapid hit\n";
    if (! ($s1 === 204 && $s2 === 429)) {
        $fail++;
    }

    Ad::where('id', $adId)->delete();
}

// --- 4. business-profile group gate --------------------------------------
$businessUser = User::where('username', 'can_maguza')->first();
$nonBusinessUser = User::where('username', 'ada_lefkosa')->first();

if ($businessUser && $nonBusinessUser) {
    [, $raw] = httpJson('GET', "$baseUrl/api/users/{$businessUser->id}");
    $decoded = json_decode($raw, true);
    $addr = $decoded['data']['attributes']['businessAddress'] ?? null;
    echo ($addr ? "[OK]" : "[FAIL]")." business-group user exposes businessAddress ($addr)\n";
    if (! $addr) {
        $fail++;
    }

    [, $raw] = httpJson('GET', "$baseUrl/api/users/{$nonBusinessUser->id}");
    $decoded = json_decode($raw, true);
    // Tobscure omits null attributes entirely (same pattern as
    // `anonymousModLabel` elsewhere in this codebase) - missing key is the
    // hidden/gated outcome, same as an explicit null.
    $hidden = ! array_key_exists('businessAddress', $decoded['data']['attributes'] ?? [])
        || $decoded['data']['attributes']['businessAddress'] === null;
    echo ($hidden ? "[OK]" : "[FAIL]")." non-business user businessAddress hidden\n";
    if (! $hidden) {
        $fail++;
    }
} else {
    echo "[SKIP] seed users can_maguza/ada_lefkosa not found - run seed.php first\n";
}

echo $fail === 0 ? "\nHEPSI GECTI\n" : "\n$fail SENARYO BASARISIZ\n";
exit($fail === 0 ? 0 : 1);
