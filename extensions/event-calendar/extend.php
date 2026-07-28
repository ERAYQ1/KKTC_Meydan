<?php

use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Saving;
use Flarum\Extend;
use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use KktcMeydan\EventCalendar\Api\Controller\CreateEventRsvpController;
use KktcMeydan\EventCalendar\Api\Controller\DeleteEventRsvpController;
use KktcMeydan\EventCalendar\Api\Controller\ListEventRsvpsController;
use KktcMeydan\EventCalendar\Api\Controller\ListEventsController;
use KktcMeydan\EventCalendar\Console\SendEventRemindersCommand;
use KktcMeydan\EventCalendar\EventRsvp;
use KktcMeydan\EventCalendar\Listener\SaveEventDatesToDatabase;
use KktcMeydan\EventCalendar\Notification\EventReminderBlueprint;

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
        })
        ->attribute('rsvpGoingCount', function (DiscussionSerializer $serializer, Discussion $discussion) {
            return EventRsvp::where('discussion_id', $discussion->id)->where('status', EventRsvp::STATUS_GOING)->count();
        })
        ->attribute('rsvpInterestedCount', function (DiscussionSerializer $serializer, Discussion $discussion) {
            return EventRsvp::where('discussion_id', $discussion->id)->where('status', EventRsvp::STATUS_INTERESTED)->count();
        })
        ->attribute('myRsvpStatus', function (DiscussionSerializer $serializer, Discussion $discussion) {
            $actor = $serializer->getActor();

            if (! $actor->exists) {
                return null;
            }

            $rsvp = EventRsvp::where('discussion_id', $discussion->id)->where('user_id', $actor->id)->first();

            return $rsvp ? $rsvp->status : null;
        }),

    (new Extend\Event())
        ->listen(Saving::class, SaveEventDatesToDatabase::class),

    (new Extend\Notification())
        ->type(EventReminderBlueprint::class, DiscussionSerializer::class, ['alert']),

    (new Extend\Routes('api'))
        ->get('/events', 'kktcmeydan-event-calendar.events.index', ListEventsController::class)
        ->get('/event-rsvps', 'kktcmeydan-event-calendar.rsvps.index', ListEventRsvpsController::class)
        ->post('/event-rsvps', 'kktcmeydan-event-calendar.rsvps.create', CreateEventRsvpController::class)
        ->delete('/event-rsvps/{id}', 'kktcmeydan-event-calendar.rsvps.delete', DeleteEventRsvpController::class),

    (new Extend\Console())
        ->command(SendEventRemindersCommand::class)
        ->schedule(SendEventRemindersCommand::class, function (ScheduledEvent $event) {
            $event->everyFiveMinutes()->withoutOverlapping();
        }),
];
