<?php

use Flarum\Api\Controller as C;
use Flarum\Api\Serializer\BasicDiscussionSerializer;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Saving as DiscussionSaving;
use Flarum\Extend;
use Flarum\Post\Event\Saving as PostSaving;
use Flarum\Post\Post;
use Flarum\User\User;
use KktcMeydan\AnonymousPosting\AnonymousMasker;
use KktcMeydan\AnonymousPosting\Listener\SetDiscussionAnonymousFlag;
use KktcMeydan\AnonymousPosting\Listener\SetPostAnonymousFlag;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Locales(__DIR__.'/locale')),

    (new Extend\Model(Discussion::class))
        ->cast('is_anonymous', 'bool'),

    (new Extend\Model(Post::class))
        ->cast('is_anonymous', 'bool'),

    (new Extend\Event())
        ->listen(DiscussionSaving::class, SetDiscussionAnonymousFlag::class)
        ->listen(PostSaving::class, SetPostAnonymousFlag::class),

    (new Extend\ApiSerializer(PostSerializer::class))
        ->attribute('isAnonymous', function (PostSerializer $serializer, Post $post) {
            return (bool) $post->is_anonymous;
        })
        ->attribute('anonymousModLabel', function (PostSerializer $serializer, Post $post) {
            if (! $post->is_anonymous) {
                return null;
            }

            if (! $serializer->getActor()->hasPermission('discussion.viewIpsPosts')) {
                return null;
            }

            $realUser = User::find($post->user_id);

            return $realUser ? "[Anonim] {$realUser->username} (IP: {$post->ip_address})" : null;
        }),

    (new Extend\ApiSerializer(BasicDiscussionSerializer::class))
        ->attribute('isAnonymous', function ($serializer, Discussion $discussion) {
            return (bool) $discussion->is_anonymous;
        })
        ->attribute('anonymousModLabel', function ($serializer, Discussion $discussion) {
            if (! $discussion->is_anonymous) {
                return null;
            }

            if (! $serializer->getActor()->hasPermission('discussion.viewIpsPosts')) {
                return null;
            }

            $realUser = User::find($discussion->user_id);

            return $realUser ? "[Anonim] {$realUser->username}" : null;
        }),

    // Strip the real `user` relationship from anonymous posts/discussions for
    // every viewer, on every endpoint that can hand one out. Moderators get
    // the real identity back via the `anonymousModLabel` attribute above.
    (new Extend\ApiController(C\ListPostsController::class))
        ->prepareDataForSerialization([AnonymousMasker::class, 'maskPosts']),
    (new Extend\ApiController(C\ShowPostController::class))
        ->prepareDataForSerialization([AnonymousMasker::class, 'maskPosts']),
    (new Extend\ApiController(C\CreatePostController::class))
        ->prepareDataForSerialization([AnonymousMasker::class, 'maskPosts']),
    (new Extend\ApiController(C\UpdatePostController::class))
        ->prepareDataForSerialization([AnonymousMasker::class, 'maskPosts']),

    (new Extend\ApiController(C\ListDiscussionsController::class))
        ->prepareDataForSerialization([AnonymousMasker::class, 'maskDiscussions'])
        ->prepareDataForSerialization([AnonymousMasker::class, 'maskPosts']),
    (new Extend\ApiController(C\ShowDiscussionController::class))
        ->prepareDataForSerialization([AnonymousMasker::class, 'maskDiscussions'])
        ->prepareDataForSerialization([AnonymousMasker::class, 'maskPosts']),
    (new Extend\ApiController(C\CreateDiscussionController::class))
        ->prepareDataForSerialization([AnonymousMasker::class, 'maskDiscussions'])
        ->prepareDataForSerialization([AnonymousMasker::class, 'maskPosts']),
    (new Extend\ApiController(C\UpdateDiscussionController::class))
        ->prepareDataForSerialization([AnonymousMasker::class, 'maskDiscussions'])
        ->prepareDataForSerialization([AnonymousMasker::class, 'maskPosts']),
];
