<?php

namespace KktcMeydan\EventCalendar\Api\Controller;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Support\Arr;
use KktcMeydan\EventCalendar\EventRsvp;
use Psr\Http\Message\ServerRequestInterface;

class DeleteEventRsvpController extends AbstractDeleteController
{
    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);
        $id = (int) Arr::get($request->getQueryParams(), 'id');

        $rsvp = EventRsvp::findOrFail($id);

        if ($rsvp->user_id !== $actor->id && ! $actor->isAdmin()) {
            throw new PermissionDeniedException;
        }

        $rsvp->delete();
    }
}
