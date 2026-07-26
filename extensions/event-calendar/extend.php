<?php

use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Saving;
use Flarum\Extend;
use KktcMeydan\EventCalendar\Listener\SaveEventDatesToDatabase;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Locales(__DIR__.'/locale')),

    (new Extend\Model(Discussion::class))
        ->cast('event_start_at', 'datetime')
        ->cast('event_end_at', 'datetime'),

    (new Extend\ApiSerializer(DiscussionSerializer::class))
        ->attribute('eventStartAt', function (DiscussionSerializer $serializer, Discussion $discussion) {
            return $serializer->formatDate($discussion->event_start_at);
        })
        ->attribute('eventEndAt', function (DiscussionSerializer $serializer, Discussion $discussion) {
            return $serializer->formatDate($discussion->event_end_at);
        }),

    (new Extend\Event())
        ->listen(Saving::class, SaveEventDatesToDatabase::class),
];
