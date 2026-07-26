<?php

namespace KktcMeydan\AnonymousPosting\Listener;

use Flarum\Foundation\ValidationException;
use Flarum\Post\Event\Saving;
use Flarum\Post\Post;
use KktcMeydan\AnonymousPosting\ContentFilter;

class SetPostAnonymousFlag
{
    const ALLOWED_TAG_SLUG = 'serbest';

    public function handle(Saving $event)
    {
        $attributes = $event->data['attributes'] ?? [];

        if (! array_key_exists('isAnonymous', $attributes)) {
            return;
        }

        $post = $event->post;
        $isAnonymous = (bool) $attributes['isAnonymous'];

        if ($isAnonymous && ! $this->discussionAllowsAnonymous($post)) {
            $isAnonymous = false;
        }

        $post->is_anonymous = $isAnonymous;

        if (! $isAnonymous) {
            return;
        }

        $content = (string) ($attributes['content'] ?? $post->content ?? '');

        if (ContentFilter::containsProfanity($content)) {
            throw new ValidationException([
                'content' => 'İçerik topluluk kurallarına aykırı ifadeler içermektedir.',
            ]);
        }

        if (ContentFilter::containsPhoneNumber($content)) {
            // Force the post (and, for a first post, its discussion) into the
            // moderation queue. Done in afterSave with a second write so the
            // outcome doesn't depend on listener registration order against
            // flarum/approval's own Saving listener, which may run before or
            // after this one and would otherwise be able to clobber it.
            $post->afterSave(function (Post $post) {
                $post->is_approved = false;
                $post->save();

                if ($post->number == 1 && $post->discussion) {
                    $post->discussion->is_approved = false;
                    $post->discussion->save();
                }
            });
        }
    }

    private function discussionAllowsAnonymous(Post $post): bool
    {
        $discussion = $post->discussion;

        if (! $discussion) {
            return false;
        }

        return $discussion->tags()->pluck('slug')->contains(self::ALLOWED_TAG_SLUG);
    }
}
