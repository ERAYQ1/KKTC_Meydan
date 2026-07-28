<?php

namespace KktcMeydan\BusinessProfile\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Illuminate\Support\Arr;
use KktcMeydan\BusinessProfile\Api\Serializer\BusinessReviewSerializer;
use KktcMeydan\BusinessProfile\BusinessReview;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListBusinessReviewsController extends AbstractListController
{
    public $serializer = BusinessReviewSerializer::class;

    public $include = ['reviewer'];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $filter = (array) Arr::get($request->getQueryParams(), 'filter', []);
        $businessUserId = (int) Arr::get($filter, 'business');

        return BusinessReview::where('business_user_id', $businessUserId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
