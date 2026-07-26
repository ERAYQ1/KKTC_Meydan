<?php

use Flarum\Database\Migration;
use Flarum\Group\Group;

return Migration::addPermissions([
    'discussion.editReportStatus' => Group::MODERATOR_ID,
]);
