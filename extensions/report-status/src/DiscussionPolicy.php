<?php

namespace KktcMeydan\ReportStatus;

use Flarum\Discussion\Discussion;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class DiscussionPolicy extends AbstractPolicy
{
    public function editReportStatus(User $actor, Discussion $discussion)
    {
        if ($actor->hasPermission('discussion.editReportStatus')) {
            return $this->allow();
        }
    }
}
