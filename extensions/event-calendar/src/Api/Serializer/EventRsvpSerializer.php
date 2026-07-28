<?php

namespace KktcMeydan\EventCalendar\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\UserSerializer;
use KktcMeydan\EventCalendar\EventRsvp;

class EventRsvpSerializer extends AbstractSerializer
{
    protected $type = 'event-rsvps';

    protected function getDefaultAttributes($rsvp)
    {
        /** @var EventRsvp $rsvp */
        return [
            'discussionId' => $rsvp->discussion_id,
            'status' => $rsvp->status,
            'createdAt' => $this->formatDate($rsvp->created_at),
        ];
    }

    protected function user($rsvp)
    {
        /** @var EventRsvp $rsvp */
        return $this->hasOne($rsvp, UserSerializer::class, 'user');
    }
}
