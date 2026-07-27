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
use KktcMeydan\AnonymousPosting\Provider\AnonymityQueryProvider;

return [
    // Yazar bazli sorgu yollarini (filter[author], filter[q]=author:X)
    // anonim satirlari eleyecek sekilde degistirir. Yanittaki iliskiyi
    // gizlemek yetmez: sorgunun kendisi yazara gore daraltilmissa sonuc
    // kumesi zaten kimligi ele verir.
    (new Extend\ServiceProvider())
        ->register(AnonymityQueryProvider::class),

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

    // Anonim icerikten kimlik tasiyan TUM iliskileri (user, editedUser,
    // hiddenUser, lastPostedUser + gomulu firstPost/lastPost/mostRelevantPost
    // ve bildirimlerdeki fromUser) her izleyici icin soker. Moderatorler
    // gercek kimligi yukaridaki `anonymousModLabel` attribute'undan alir.
    //
    // Tek bir `mask` callback'i model tipine gore dallaniyor - eskiden ayri
    // olan maskPosts/maskDiscussions ikilisi, bir Discussion koleksiyonunda
    // gomulu post'lari hic gezmedigi icin `mostRelevantPost.user`'i aciyordu.
    (new Extend\ApiController(C\ListPostsController::class))
        ->prepareDataForSerialization([AnonymousMasker::class, 'mask']),
    (new Extend\ApiController(C\ShowPostController::class))
        ->prepareDataForSerialization([AnonymousMasker::class, 'mask']),
    (new Extend\ApiController(C\CreatePostController::class))
        ->prepareDataForSerialization([AnonymousMasker::class, 'mask']),
    (new Extend\ApiController(C\UpdatePostController::class))
        ->prepareDataForSerialization([AnonymousMasker::class, 'mask']),

    (new Extend\ApiController(C\ListDiscussionsController::class))
        ->prepareDataForSerialization([AnonymousMasker::class, 'mask']),
    (new Extend\ApiController(C\ShowDiscussionController::class))
        ->prepareDataForSerialization([AnonymousMasker::class, 'mask']),
    (new Extend\ApiController(C\CreateDiscussionController::class))
        ->prepareDataForSerialization([AnonymousMasker::class, 'mask']),
    (new Extend\ApiController(C\UpdateDiscussionController::class))
        ->prepareDataForSerialization([AnonymousMasker::class, 'mask']),

    // Abone olunan anonim konuya gelen "yeni yanit" bildiriminin `fromUser`
    // iliskisi gercek yazari ele veriyordu.
    (new Extend\ApiController(C\ListNotificationsController::class))
        ->prepareDataForSerialization([AnonymousMasker::class, 'mask']),
];
