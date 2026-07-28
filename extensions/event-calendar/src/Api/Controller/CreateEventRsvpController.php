<?php

namespace KktcMeydan\EventCalendar\Api\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Discussion\Discussion;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Support\Arr;
use KktcMeydan\EventCalendar\Api\Serializer\EventRsvpSerializer;
use KktcMeydan\EventCalendar\EventRsvp;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreateEventRsvpController extends AbstractCreateController
{
    public $serializer = EventRsvpSerializer::class;

    public $include = ['user'];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);

        if (! $actor->exists) {
            throw new PermissionDeniedException;
        }

        $attributes = (array) Arr::get($request->getParsedBody(), 'data.attributes', []);
        $discussionId = (int) Arr::get($attributes, 'discussionId');
        $status = (string) Arr::get($attributes, 'status', EventRsvp::STATUS_GOING);

        if (! in_array($status, [EventRsvp::STATUS_GOING, EventRsvp::STATUS_INTERESTED], true)) {
            throw new ValidationException(['status' => 'Gecersiz katilim durumu.']);
        }

        $discussion = Discussion::query()->whereVisibleTo($actor)->find($discussionId);

        if (! $discussion || ! $discussion->event_start_at) {
            throw new ValidationException(['discussionId' => 'Gecerli bir etkinlik bulunamadi.']);
        }

        $rsvp = EventRsvp::where('discussion_id', $discussionId)
            ->where('user_id', $actor->id)
            ->first() ?? new EventRsvp;

        $rsvp->discussion_id = $discussionId;
        $rsvp->user_id = $actor->id;
        $rsvp->status = $status;
        $rsvp->save();

        return $rsvp;
    }
}
