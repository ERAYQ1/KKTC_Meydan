<?php

namespace KktcMeydan\BlockUser\Visibility;

use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use KktcMeydan\BlockUser\UserBlock;

/**
 * Registered as a Discussion/Post visibility scoper: hides content authored
 * by users the actor has blocked from all listing and show endpoints that
 * respect `whereVisibleTo` (ListDiscussions, ListPosts, discussion search, ...).
 */
class FilterBlockedAuthors
{
    public function __invoke(User $actor, Builder $query): void
    {
        if ($actor->isGuest()) {
            return;
        }

        $blockedIds = UserBlock::blockedIdsFor($actor->id);

        if (empty($blockedIds)) {
            return;
        }

        $query->whereNotIn($query->getModel()->getTable().'.user_id', $blockedIds);
    }
}
