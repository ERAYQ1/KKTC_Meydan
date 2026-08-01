<?php

use Flarum\Api\Controller\ShowForumController;
use Flarum\Api\Serializer\CurrentUserSerializer;
use Flarum\Api\Serializer\UserSerializer;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\Post;
use Flarum\User\User;
use KktcMeydan\BlockUser\Api\Controller\BlockUserController;
use KktcMeydan\BlockUser\Api\Controller\UnblockUserController;
use KktcMeydan\BlockUser\UserBlock;
use KktcMeydan\BlockUser\Visibility\FilterBlockedAuthors;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Locales(__DIR__.'/locale')),

    (new Extend\Routes('api'))
        ->post('/users/{id}/block', 'kktcmeydan-block-user.block', BlockUserController::class)
        ->delete('/users/{id}/block', 'kktcmeydan-block-user.unblock', UnblockUserController::class),

    (new Extend\Model(User::class))
        ->belongsToMany('blockedUsers', User::class, 'user_blocks', 'user_id', 'blocked_user_id'),

    (new Extend\ApiSerializer(UserSerializer::class))
        ->attribute('isBlocked', function ($serializer, User $user) {
            $actor = $serializer->getActor();

            if ($actor->isGuest() || $actor->id === $user->id) {
                return false;
            }

            return UserBlock::isBlocked($actor->id, $user->id);
        }),

    (new Extend\ApiSerializer(CurrentUserSerializer::class))
        ->hasMany('blockedUsers', UserSerializer::class),

    (new Extend\ApiController(ShowForumController::class))
        ->addInclude('actor.blockedUsers'),

    (new Extend\ModelVisibility(Discussion::class))
        ->scope(FilterBlockedAuthors::class),

    (new Extend\ModelVisibility(Post::class))
        ->scope(FilterBlockedAuthors::class),
];
