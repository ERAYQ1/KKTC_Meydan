<?php

namespace KktcMeydan\EventCalendar\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Illuminate\Support\Arr;
use KktcMeydan\EventCalendar\Api\Serializer\EventRsvpSerializer;
use KktcMeydan\EventCalendar\EventRsvp;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListEventRsvpsController extends AbstractListController
{
    public $serializer = EventRsvpSerializer::class;

    public $include = ['user'];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $filter = (array) Arr::get($request->getQueryParams(), 'filter', []);
        $discussionId = (int) Arr::get($filter, 'discussion');

        return EventRsvp::where('discussion_id', $discussionId)
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
