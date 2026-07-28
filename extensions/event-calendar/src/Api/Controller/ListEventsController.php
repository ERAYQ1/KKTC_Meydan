<?php

namespace KktcMeydan\EventCalendar\Api\Controller;

use Carbon\Carbon;
use Flarum\Api\Controller\AbstractListController;
use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Discussion\Discussion;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListEventsController extends AbstractListController
{
    public $serializer = DiscussionSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $filter = (array) Arr::get($request->getQueryParams(), 'filter', []);

        $start = $this->parseDate(Arr::get($filter, 'start')) ?? Carbon::now()->startOfMonth();
        $end = $this->parseDate(Arr::get($filter, 'end')) ?? (clone $start)->endOfMonth();

        return Discussion::query()
            ->whereVisibleTo($actor)
            ->whereNotNull('event_start_at')
            ->where('event_start_at', '<=', $end)
            ->where(function ($query) use ($start) {
                $query->where('event_end_at', '>=', $start)
                    ->orWhere(function ($query) use ($start) {
                        $query->whereNull('event_end_at')->where('event_start_at', '>=', $start);
                    });
            })
            ->orderBy('event_start_at', 'asc')
            ->get();
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }
}
