<?php

namespace KktcMeydan\AdsManager\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use KktcMeydan\AdsManager\Ad;
use KktcMeydan\AdsManager\Api\Serializer\AdSerializer;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListAdsController extends AbstractListController
{
    public $serializer = AdSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $queryParams = $request->getQueryParams();
        $filter = (array) Arr::get($queryParams, 'filter', []);

        $query = Ad::query();

        // Admins managing ads in the admin panel see every ad (incl. inactive);
        // the forum-facing banner only ever wants live, targeted ads.
        if (! ($actor->isAdmin() && Arr::get($filter, 'all'))) {
            $query->where('is_active', true);

            $tag = Arr::get($filter, 'tag');

            if ($tag) {
                $query->where(function ($q) use ($tag) {
                    $q->where('target_category_slug', $tag)
                        ->orWhere('target_district_slug', $tag)
                        ->orWhere('target_university_slug', $tag);
                });
            } else {
                $query->whereNull('target_category_slug')
                    ->whereNull('target_district_slug')
                    ->whereNull('target_university_slug');
            }
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
