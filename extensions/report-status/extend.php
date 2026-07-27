<?php

use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Saving;
use Flarum\Extend;
use KktcMeydan\ReportStatus\DiscussionPolicy;
use KktcMeydan\ReportStatus\Listener\SaveReportStatusToDatabase;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Locales(__DIR__.'/locale')),

    (new Extend\Model(Discussion::class))
        ->cast('report_status', 'string'),

    (new Extend\ApiSerializer(DiscussionSerializer::class))
        ->attribute('reportStatus', function (DiscussionSerializer $serializer, Discussion $discussion) {
            return $discussion->report_status;
        })
        ->attribute('canEditReportStatus', function (DiscussionSerializer $serializer, Discussion $discussion) {
            $actor = $serializer->getActor();

            return $actor->can('editReportStatus', $discussion);
        }),

    // Single source of truth for the status list: SaveReportStatusToDatabase
    // validates against this same array server-side, so JS can't drift from
    // what the server actually accepts.
    (new Extend\ApiSerializer(ForumSerializer::class))
        ->attribute('reportStatuses', function () {
            return SaveReportStatusToDatabase::VALID_STATUSES;
        }),

    (new Extend\Policy())
        ->modelPolicy(Discussion::class, DiscussionPolicy::class),

    (new Extend\Event())
        ->listen(Saving::class, SaveReportStatusToDatabase::class),
];
