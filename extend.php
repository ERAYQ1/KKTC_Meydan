<?php

/*
 * Site-local customizations for KKTC Meydan.
 * Loaded automatically by Flarum (see Flarum\Foundation\Site::loadExtenders).
 */

namespace KktcMeydan;

use Flarum\Announcements\AnnouncementsFetcher;
use Flarum\Api\Controller\ListDiscussionsController;
use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Extend;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\User\User;
use IanM\FollowUsers\Api\AddBasicUserAttributes;
use IanM\FollowUsers\FollowState;

class NullAnnouncementsFetcher extends AnnouncementsFetcher
{
    public function fetch(): array
    {
        return [];
    }
}

class DisableAnnouncementsServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->bind(AnnouncementsFetcher::class, NullAnnouncementsFetcher::class);
    }
}

/**
 * ianm/follow-users always resolves a "followed" state for every user in a
 * response (e.g. every author on the discussion list), even for guests, who
 * can never be following anyone. Its own eager-load helpers (LoadRelations)
 * already skip guests everywhere else, but AddBasicUserAttributes doesn't
 * check, so FollowState::forFromRelation() falls through to a per-user
 * FollowState::for() query — one `select ... from user_followers` per
 * distinct author on the page, for every anonymous visitor. Overriding the
 * container binding (rather than patching vendor/) keeps this fix intact
 * across `composer update`.
 */
class GuestSafeAddBasicUserAttributes extends AddBasicUserAttributes
{
    public function __invoke(BasicUserSerializer $serializer, User $user, array $attributes): array
    {
        $actor = $serializer->getActor();

        if (!$actor->isGuest()) {
            return parent::__invoke($serializer, $user, $attributes);
        }

        $attributes['followed'] = null;

        if ((bool) $this->settings->get('ianm-follow-users.stats-on-profile')) {
            $attributes['followerCount'] = FollowState::getFollowerCount($user);
            $attributes['followingCount'] = FollowState::getFollowingCount($user);
        }

        return $attributes;
    }
}

class FollowUsersPerfServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->bind(AddBasicUserAttributes::class, GuestSafeAddBasicUserAttributes::class);
    }
}

return [
    (new Extend\ServiceProvider())
        ->register(DisableAnnouncementsServiceProvider::class)
        ->register(FollowUsersPerfServiceProvider::class),

    // Turkish translations for third-party fof/* and ianm/* packages, which
    // only ship English locale files. Flarum merges every registered
    // Locales directory into the same 'tr' catalogue, so this overlay just
    // adds the missing keys (see locale-overrides/tr.yml for details).
    (new Extend\Locales(__DIR__.'/locale-overrides')),

    // fof/best-answer registers `bestAnswerPost` as an optional include on
    // the discussion list (ListDiscussionsController) but — unlike its own
    // ShowDiscussionController/ListPostsController registrations — never
    // pairs it with a `->load(...)` call. Its BasicDiscussionAttributes
    // still unconditionally reads $discussion->bestAnswerPost for every
    // discussion, so it lazy-loads one-by-one: an extra
    // `select * from posts where id = ?` per discussion that has a best
    // answer set, on every discussion list page view. Adding our own
    // `->load()` for the same controller batches it into a single query
    // (Flarum merges eager-load registrations from multiple extensions).
    (new Extend\ApiController(ListDiscussionsController::class))
        ->load(['bestAnswerPost', 'bestAnswerPost.user']),

    // Fixes wide custom badges (classifieds price/location, report-status,
    // event-calendar) visually overlapping the discussion title in the
    // list — see less-overrides/forum.less for the root cause. Applies to
    // every viewport width, unlike extensions/mobile-ui which is scoped to
    // @media (max-width: 768px) only by design.
    (new Extend\Frontend('forum'))
        ->css(__DIR__.'/less-overrides/forum.less'),
];
