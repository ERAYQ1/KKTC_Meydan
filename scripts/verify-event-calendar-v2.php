<?php

/*
 * event-calendar v2 dogrulama betigi (RSVP + takvim endpoint + hatirlatma).
 * Kullanim: docker exec -w /var/www/html kktc_meydan_app php scripts/verify-event-calendar-v2.php
 */

require __DIR__.'/../vendor/autoload.php';

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Http\DeveloperAccessToken;
use Flarum\Notification\Notification;
use Flarum\User\User;
use KktcMeydan\EventCalendar\Console\SendEventRemindersCommand;
use KktcMeydan\EventCalendar\EventRsvp;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

$site = require __DIR__.'/../site.php';
$app = $site->bootApp();
$container = $app->getContainer();

function runReminderCommand($container): int
{
    $command = $container->make(SendEventRemindersCommand::class);

    return $command->run(new ArrayInput([]), new NullOutput());
}

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
$admin = User::find(1);
$adminToken = DeveloperAccessToken::generate($admin->id);
$adminToken->save();
$aToken = $adminToken->token;

$rsvpUser = User::where('username', 'ada_lefkosa')->first();

if (! $rsvpUser) {
    echo "[SKIP] seed user ada_lefkosa not found - run seed.php first\n";
    exit(0);
}

$rsvpToken = DeveloperAccessToken::generate($rsvpUser->id);
$rsvpToken->save();
$rToken = $rsvpToken->token;

// --- 0. Create a throwaway event discussion via direct model (avoids composer flow)
$event = new Discussion;
$event->title = 'Test Etkinlik '.time();
$event->user_id = $admin->id;
$event->created_at = Carbon::now();
$event->event_start_at = Carbon::now()->addHours(5); // inside the 24h reminder window
$event->comment_count = 1; // whereVisibleTo() hides comment_count=0 discussions from non-authors
$event->save();

// Discussion visibility requires at least one tag (this forum's tags-required
// setup) - attach the real "Keşfet" category so whereVisibleTo() doesn't hide it.
$kesfetTag = \Flarum\Tags\Tag::where('slug', 'kesfet')->firstOrFail();
$event->tags()->sync([$kesfetTag->id]);

EventRsvp::where('discussion_id', $event->id)->delete();

// --- 1. RSVP: invalid status rejected ---------------------------------------
[$status, ] = httpJson('POST', "$baseUrl/api/event-rsvps", $rToken, [
    'data' => ['attributes' => ['discussionId' => $event->id, 'status' => 'maybe']],
]);
echo ($status === 422 ? "[OK]" : "[FAIL ($status)]")." invalid rsvp status rejected\n";
if ($status !== 422) {
    $fail++;
}

// --- 2. RSVP: valid create -----------------------------------------------------
[$status, $raw] = httpJson('POST', "$baseUrl/api/event-rsvps", $rToken, [
    'data' => ['attributes' => ['discussionId' => $event->id, 'status' => 'going']],
]);
echo ($status === 201 ? "[OK]" : "[FAIL ($status)]")." valid rsvp created\n";
if ($status !== 201) {
    $fail++;
    echo "  body: $raw\n";
}

// --- 3. RSVP: re-rsvp updates in place, no duplicate row ------------------------
[$status, ] = httpJson('POST', "$baseUrl/api/event-rsvps", $rToken, [
    'data' => ['attributes' => ['discussionId' => $event->id, 'status' => 'interested']],
]);
$count = EventRsvp::where('discussion_id', $event->id)->where('user_id', $rsvpUser->id)->count();
echo ($status === 201 && $count === 1 ? "[OK]" : "[FAIL ($status, count=$count)]")." re-rsvp updates in place\n";
if (! ($status === 201 && $count === 1)) {
    $fail++;
}

// --- 4. Discussion attributes reflect rsvp counts -------------------------------
[, $raw] = httpJson('GET', "$baseUrl/api/discussions/{$event->id}");
$decoded = json_decode($raw, true);
$interestedCount = $decoded['data']['attributes']['rsvpInterestedCount'] ?? null;
echo ($interestedCount === 1 ? "[OK]" : "[FAIL ($interestedCount)]")." rsvpInterestedCount reflects rsvp\n";
if ($interestedCount !== 1) {
    $fail++;
    echo "  body: $raw\n";
}

// --- 5. RSVP on non-event discussion rejected -----------------------------------
$nonEvent = Discussion::whereNull('event_start_at')->first();
if ($nonEvent) {
    [$status, ] = httpJson('POST', "$baseUrl/api/event-rsvps", $rToken, [
        'data' => ['attributes' => ['discussionId' => $nonEvent->id, 'status' => 'going']],
    ]);
    echo ($status === 422 ? "[OK]" : "[FAIL ($status)]")." rsvp on non-event discussion rejected\n";
    if ($status !== 422) {
        $fail++;
    }
}

// --- 6. Calendar range endpoint returns this event within its month -------------
$monthStart = $event->event_start_at->copy()->startOfMonth()->toIso8601String();
$monthEnd = $event->event_start_at->copy()->endOfMonth()->toIso8601String();
[$status, $raw] = httpJson('GET', "$baseUrl/api/events?filter[start]=".urlencode($monthStart)."&filter[end]=".urlencode($monthEnd));
$decoded = json_decode($raw, true);
$found = collect($decoded['data'] ?? [])->contains(fn ($d) => (int) $d['id'] === $event->id);
echo ($status === 200 && $found ? "[OK]" : "[FAIL ($status)]")." calendar range endpoint includes the event\n";
if (! ($status === 200 && $found)) {
    $fail++;
    echo "  body: $raw\n";
}

// --- 7. Reminder command sends a notification + marks reminded_at --------------
Notification::where('type', 'kktcmeydanEventReminder')->where('subject_id', $event->id)->delete();

$exitCode = runReminderCommand($container);
$remindedAt = EventRsvp::where('discussion_id', $event->id)->where('user_id', $rsvpUser->id)->value('reminded_at');
$notified = Notification::where('type', 'kktcmeydanEventReminder')->where('subject_id', $event->id)->where('user_id', $rsvpUser->id)->exists();
echo ($exitCode === 0 && $remindedAt && $notified ? "[OK]" : "[FAIL (exit=$exitCode, reminded_at=$remindedAt, notified=".($notified ? '1' : '0').")]")." reminder command notifies rsvp'd user\n";
if (! ($exitCode === 0 && $remindedAt && $notified)) {
    $fail++;
}

// --- 8. Reminder command does not double-send on second run --------------------
runReminderCommand($container);
$notifiedCount = Notification::where('type', 'kktcmeydanEventReminder')->where('subject_id', $event->id)->where('user_id', $rsvpUser->id)->count();
echo ($notifiedCount === 1 ? "[OK]" : "[FAIL (count=$notifiedCount)]")." reminder not duplicated on second run\n";
if ($notifiedCount !== 1) {
    $fail++;
}

// --- 9. Delete: non-owner cannot delete another user's rsvp ---------------------
$rsvpId = EventRsvp::where('discussion_id', $event->id)->where('user_id', $rsvpUser->id)->value('id');
$otherUser = User::where('id', '!=', $rsvpUser->id)
    ->whereDoesntHave('groups', function ($q) { $q->where('group_id', 1); })
    ->first();
$otherToken = DeveloperAccessToken::generate($otherUser->id);
$otherToken->save();

[$status, ] = httpJson('DELETE', "$baseUrl/api/event-rsvps/$rsvpId", $otherToken->token);
echo ($status === 403 ? "[OK]" : "[FAIL ($status)]")." non-owner cannot delete another user's rsvp\n";
if ($status !== 403) {
    $fail++;
}

[$status, ] = httpJson('DELETE', "$baseUrl/api/event-rsvps/$rsvpId", $rToken);
echo ($status === 204 ? "[OK]" : "[FAIL ($status)]")." owner can delete their own rsvp\n";
if ($status !== 204) {
    $fail++;
}

// Cleanup
Notification::where('type', 'kktcmeydanEventReminder')->where('subject_id', $event->id)->delete();
EventRsvp::where('discussion_id', $event->id)->delete();
$event->delete();
$adminToken->delete();
$rsvpToken->delete();
$otherToken->delete();

echo $fail === 0 ? "\nHEPSI GECTI\n" : "\n$fail SENARYO BASARISIZ\n";
exit($fail === 0 ? 0 : 1);
