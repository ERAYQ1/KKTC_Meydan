<?php

namespace KktcMeydan\AutoModeration\Listener;

use Carbon\Carbon;
use Flarum\Flags\Flag;
use Flarum\Post\Event\Saving;
use Flarum\Post\Post;
use KktcMeydan\AutoModeration\ContentGuard;

class GuardRegulatedCategoryContent
{
    const REGULATED_TAG_SLUGS = ['saglik', 'guvenlik-acil-durum', 'kamu'];
    const SECURITY_TAG_SLUG = 'guvenlik-acil-durum';

    public function handle(Saving $event)
    {
        $attributes = $event->data['attributes'] ?? [];

        if (! array_key_exists('content', $attributes)) {
            return;
        }

        $post = $event->post;

        if (! $this->discussionInRegulatedCategory($post)) {
            return;
        }

        $content = (string) $attributes['content'];

        $hasIdNumber = ContentGuard::containsIdNumber($content);
        $hasHealthViolation = ContentGuard::containsHealthPrivacyViolation($content);
        $hasDefamation = ContentGuard::containsDefamationAgainstTarget($content);

        if (! $hasIdNumber && ! $hasHealthViolation && ! $hasDefamation) {
            return;
        }

        $isSecurityTag = $this->discussionHasTag($post, self::SECURITY_TAG_SLUG);

        // Force the queue/flag decision in afterSave (a second write) so it
        // can't be clobbered by flarum/approval's own Saving listener, which
        // may run before or after this one depending on registration order.
        $post->afterSave(function (Post $post) use ($hasIdNumber, $isSecurityTag) {
            $post->is_approved = false;
            $post->save();

            if ($post->number == 1 && $post->discussion) {
                $post->discussion->is_approved = false;
                $post->discussion->save();
            }

            if ($hasIdNumber || $isSecurityTag) {
                // firstOrCreate: afterSave can run again on a subsequent
                // edit that still trips the guard - don't stack duplicate
                // flags for the same post/type pair.
                Flag::firstOrCreate(
                    ['post_id' => $post->id, 'type' => 'kktcmeydan-auto-moderation'],
                    ['created_at' => Carbon::now()]
                );
            }
        });
    }

    private function discussionInRegulatedCategory(Post $post): bool
    {
        $discussion = $post->discussion;

        if (! $discussion) {
            return false;
        }

        return $discussion->tags()->pluck('slug')->intersect(self::REGULATED_TAG_SLUGS)->isNotEmpty();
    }

    private function discussionHasTag(Post $post, string $slug): bool
    {
        return $post->discussion->tags()->pluck('slug')->contains($slug);
    }
}
