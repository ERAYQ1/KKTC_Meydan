<?php

/*
 * business-profile v2 dogrulama betigi (harita/foto URL + yorum/puan).
 * Kullanim: docker exec -w /var/www/html kktc_meydan_app php scripts/verify-business-profile-v2.php
 */

require __DIR__.'/../vendor/autoload.php';

use Flarum\Http\DeveloperAccessToken;
use Flarum\User\User;
use KktcMeydan\BusinessProfile\BusinessReview;

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

$business = User::where('username', 'can_maguza')->first();
$reviewer = User::where('username', 'ada_lefkosa')->first();

if (! $business || ! $reviewer) {
    echo "[SKIP] seed users can_maguza/ada_lefkosa not found - run seed.php first\n";
    exit(0);
}

BusinessReview::where('business_user_id', $business->id)->where('reviewer_user_id', $reviewer->id)->delete();

$reviewerToken = DeveloperAccessToken::generate($reviewer->id);
$reviewerToken->save();
$rToken = $reviewerToken->token;

$businessToken = DeveloperAccessToken::generate($business->id);
$businessToken->save();
$bToken = $businessToken->token;

// --- 1. Map/photo URL validation on preferences save ---------------------
[$status, $raw] = httpJson('PATCH', "$baseUrl/api/users/{$business->id}", $bToken, [
    'data' => ['attributes' => ['preferences' => ['business_map_url' => 'javascript:alert(1)']]],
]);
echo ($status === 422 ? "[OK]" : "[FAIL ($status)]")." malicious business_map_url scheme rejected\n";
if ($status !== 422) {
    $fail++;
    echo "  body: $raw\n";
}

[$status, $raw] = httpJson('PATCH', "$baseUrl/api/users/{$business->id}", $bToken, [
    'data' => ['attributes' => ['preferences' => [
        'business_map_url' => 'https://maps.google.com/?q=test',
        'business_photo_url' => 'https://example.com/photo.jpg',
    ]]],
]);
echo ($status === 200 ? "[OK]" : "[FAIL ($status)]")." valid https map/photo URLs accepted\n";
if ($status !== 200) {
    $fail++;
    echo "  body: $raw\n";
}

[, $raw] = httpJson('GET', "$baseUrl/api/users/{$business->id}");
$decoded = json_decode($raw, true);
$mapUrl = $decoded['data']['attributes']['businessMapUrl'] ?? null;
$isBusiness = $decoded['data']['attributes']['isBusinessUser'] ?? null;
echo ($mapUrl === 'https://maps.google.com/?q=test' ? "[OK]" : "[FAIL]")." businessMapUrl exposed publicly ($mapUrl)\n";
if ($mapUrl !== 'https://maps.google.com/?q=test') {
    $fail++;
}
echo ($isBusiness === true ? "[OK]" : "[FAIL]")." isBusinessUser true for business group member\n";
if ($isBusiness !== true) {
    $fail++;
}

// --- 2. Reviews: cannot review self ---------------------------------------
[$status, $raw] = httpJson('POST', "$baseUrl/api/business-reviews", $bToken, [
    'data' => ['attributes' => ['businessUserId' => $business->id, 'rating' => 5]],
]);
echo ($status === 422 ? "[OK]" : "[FAIL ($status)]")." self-review rejected\n";
if ($status !== 422) {
    $fail++;
    echo "  body: $raw\n";
}

// --- 3. Reviews: rating out of range ---------------------------------------
[$status, ] = httpJson('POST', "$baseUrl/api/business-reviews", $rToken, [
    'data' => ['attributes' => ['businessUserId' => $business->id, 'rating' => 9]],
]);
echo ($status === 422 ? "[OK]" : "[FAIL ($status)]")." out-of-range rating rejected\n";
if ($status !== 422) {
    $fail++;
}

// --- 4. Reviews: valid create + duplicate becomes update (not duplicate row)
[$status, $raw] = httpJson('POST', "$baseUrl/api/business-reviews", $rToken, [
    'data' => ['attributes' => ['businessUserId' => $business->id, 'rating' => 4, 'comment' => 'Iyi hizmet']],
]);
echo ($status === 201 ? "[OK]" : "[FAIL ($status)]")." valid review created\n";
if ($status !== 201) {
    $fail++;
    echo "  body: $raw\n";
}

[$status, $raw] = httpJson('POST', "$baseUrl/api/business-reviews", $rToken, [
    'data' => ['attributes' => ['businessUserId' => $business->id, 'rating' => 5, 'comment' => 'Guncellendi']],
]);
$count = BusinessReview::where('business_user_id', $business->id)->where('reviewer_user_id', $reviewer->id)->count();
echo ($status === 201 && $count === 1 ? "[OK]" : "[FAIL ($status, count=$count)]")." same reviewer re-review updates in place (no duplicate)\n";
if (! ($status === 201 && $count === 1)) {
    $fail++;
    echo "  body: $raw\n";
}

// --- 5. Avg rating / count reflected on business user -----------------------
[, $raw] = httpJson('GET', "$baseUrl/api/users/{$business->id}");
$decoded = json_decode($raw, true);
$avg = $decoded['data']['attributes']['businessAvgRating'] ?? null;
$cnt = $decoded['data']['attributes']['businessReviewCount'] ?? null;
echo ((float) $avg === 5.0 && $cnt === 1 ? "[OK]" : "[FAIL (avg=$avg, count=$cnt)]")." businessAvgRating/businessReviewCount reflect review\n";
if (! ((float) $avg === 5.0 && $cnt === 1)) {
    $fail++;
}

// --- 6. List reviews filtered by business ------------------------------------
[$status, $raw] = httpJson('GET', "$baseUrl/api/business-reviews?filter[business]={$business->id}");
$decoded = json_decode($raw, true);
$listCount = count($decoded['data'] ?? []);
echo ($status === 200 && $listCount === 1 ? "[OK]" : "[FAIL ($status, $listCount)]")." list endpoint returns this business's reviews\n";
if (! ($status === 200 && $listCount === 1)) {
    $fail++;
    echo "  body: $raw\n";
}

// --- 7. Delete: only the reviewer (or admin) can delete their review --------
$reviewId = $decoded['data'][0]['id'] ?? null;
$otherUser = User::where('id', '!=', $reviewer->id)->where('id', '!=', $business->id)
    ->whereDoesntHave('groups', function ($q) { $q->where('group_id', 1); })
    ->first();
$otherToken = DeveloperAccessToken::generate($otherUser->id);
$otherToken->save();

[$status, ] = httpJson('DELETE', "$baseUrl/api/business-reviews/$reviewId", $otherToken->token);
echo ($status === 403 ? "[OK]" : "[FAIL ($status)]")." non-owner cannot delete someone else's review\n";
if ($status !== 403) {
    $fail++;
}

[$status, ] = httpJson('DELETE', "$baseUrl/api/business-reviews/$reviewId", $rToken);
echo ($status === 204 ? "[OK]" : "[FAIL ($status)]")." reviewer can delete their own review\n";
if ($status !== 204) {
    $fail++;
}

// Cleanup dev tokens
$reviewerToken->delete();
$businessToken->delete();
$otherToken->delete();

echo $fail === 0 ? "\nHEPSI GECTI\n" : "\n$fail SENARYO BASARISIZ\n";
exit($fail === 0 ? 0 : 1);
