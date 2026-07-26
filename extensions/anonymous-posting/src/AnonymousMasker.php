<?php

namespace KktcMeydan\AnonymousPosting;

use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Illuminate\Database\Eloquent\Collection;

/**
 * Strips the real `user` relationship from anonymous discussions/posts before
 * they're serialized, for every viewer (including moderators). Moderators get
 * the real identity back separately, via the `anonymousModLabel` serializer
 * attribute, which is gated on the `discussion.viewIpsPosts` permission.
 */
class AnonymousMasker
{
    public static function maskPosts($controller, $data): void
    {
        foreach (self::collect($data, Post::class, 'posts') as $post) {
            if ($post->is_anonymous) {
                $post->setRelation('user', null);
            }
        }
    }

    public static function maskDiscussions($controller, $data): void
    {
        foreach (self::collect($data, Discussion::class, 'discussions') as $discussion) {
            if ($discussion->is_anonymous) {
                $discussion->setRelation('user', null);
            }
        }
    }

    private static function collect($data, string $modelClass, string $relation): iterable
    {
        if ($data instanceof $modelClass) {
            return [$data];
        }

        if ($data instanceof Collection) {
            return $data->filter(function ($item) use ($modelClass) {
                return $item instanceof $modelClass;
            });
        }

        if (is_object($data) && isset($data->{$relation}) && $data->{$relation} instanceof Collection) {
            return $data->{$relation};
        }

        return [];
    }
}
