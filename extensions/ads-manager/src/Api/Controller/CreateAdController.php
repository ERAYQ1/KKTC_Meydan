<?php

namespace KktcMeydan\AdsManager\Api\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Http\RequestUtil;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Support\Arr;
use KktcMeydan\AdsManager\Ad;
use KktcMeydan\AdsManager\AdUrlValidator;
use KktcMeydan\AdsManager\Api\Serializer\AdSerializer;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreateAdController extends AbstractCreateController
{
    public $serializer = AdSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);

        if (! $actor->isAdmin()) {
            throw new PermissionDeniedException;
        }

        $attributes = (array) Arr::get($request->getParsedBody(), 'data.attributes', []);

        $imageUrl = (string) Arr::get($attributes, 'imageUrl', '');
        $targetUrl = (string) Arr::get($attributes, 'targetUrl', '');

        AdUrlValidator::assertValid($imageUrl, 'imageUrl');
        AdUrlValidator::assertValid($targetUrl, 'targetUrl');

        $ad = new Ad;
        $ad->title = strip_tags(trim((string) Arr::get($attributes, 'title', '')));
        $ad->image_url = $imageUrl;
        $ad->target_url = $targetUrl;
        $ad->target_category_slug = Arr::get($attributes, 'targetCategorySlug') ?: null;
        $ad->target_district_slug = Arr::get($attributes, 'targetDistrictSlug') ?: null;
        $ad->target_university_slug = Arr::get($attributes, 'targetUniversitySlug') ?: null;
        $ad->is_active = (bool) Arr::get($attributes, 'isActive', true);
        $ad->impressions_count = 0;
        $ad->clicks_count = 0;
        $ad->save();

        return $ad;
    }
}
