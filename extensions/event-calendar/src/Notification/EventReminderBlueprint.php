<?php

namespace KktcMeydan\EventCalendar\Notification;

use Flarum\Discussion\Discussion;
use Flarum\Notification\Blueprint\BlueprintInterface;

class EventReminderBlueprint implements BlueprintInterface
{
    /**
     * @var Discussion
     */
    public $discussion;

    public function __construct(Discussion $discussion)
    {
        $this->discussion = $discussion;
    }

    public function getSubject()
    {
        return $this->discussion;
    }

    public function getFromUser()
    {
        return null;
    }

    public function getData()
    {
        return [
            'eventStartAt' => optional($this->discussion->event_start_at)->toIso8601String(),
        ];
    }

    public static function getType()
    {
        return 'kktcmeydanEventReminder';
    }

    public static function getSubjectModel()
    {
        return Discussion::class;
    }
}
