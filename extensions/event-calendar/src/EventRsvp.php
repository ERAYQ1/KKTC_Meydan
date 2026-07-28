<?php

namespace KktcMeydan\EventCalendar;

use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\User\User;

/**
 * @property int $id
 * @property int $discussion_id
 * @property int $user_id
 * @property string $status
 * @property \Carbon\Carbon|null $reminded_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Flarum\User\User|null $user
 * @property-read \Flarum\Discussion\Discussion|null $discussion
 */
class EventRsvp extends AbstractModel
{
    protected $table = 'event_rsvps';

    const STATUS_GOING = 'going';
    const STATUS_INTERESTED = 'interested';

    protected $casts = [
        'reminded_at' => 'datetime',
    ];

    public $timestamps = true;

    public function discussion()
    {
        return $this->belongsTo(Discussion::class, 'discussion_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
