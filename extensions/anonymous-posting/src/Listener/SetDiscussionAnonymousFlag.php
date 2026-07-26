<?php

namespace KktcMeydan\AnonymousPosting\Listener;

use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Saving;
use Flarum\Tags\Tag;

class SetDiscussionAnonymousFlag
{
    const ALLOWED_TAG_SLUG = 'serbest';

    public function handle(Saving $event)
    {
        $attributes = $event->data['attributes'] ?? [];

        if (! array_key_exists('isAnonymous', $attributes)) {
            return;
        }

        $discussion = $event->discussion;
        $isAnonymous = (bool) $attributes['isAnonymous'];

        if ($isAnonymous && ! $this->isInAllowedCategory($discussion, $event->data)) {
            $isAnonymous = false;
        }

        $discussion->is_anonymous = $isAnonymous;
    }

    private function isInAllowedCategory(Discussion $discussion, array $data): bool
    {
        // Tags sent along with this save take priority: at discussion-creation
        // time the relation isn't attached to the model yet (flarum/tags syncs
        // it in an afterSave hook that hasn't run when this listener fires).
        if (isset($data['relationships']['tags']['data'])) {
            $ids = array_map(function ($link) {
                return (int) $link['id'];
            }, (array) $data['relationships']['tags']['data']);

            return Tag::whereIn('id', $ids)->pluck('slug')->contains(self::ALLOWED_TAG_SLUG);
        }

        if ($discussion->exists) {
            return $discussion->tags()->pluck('slug')->contains(self::ALLOWED_TAG_SLUG);
        }

        return false;
    }
}
