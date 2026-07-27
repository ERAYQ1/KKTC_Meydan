<?php

namespace KktcMeydan\AdsManager\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Support\Arr;
use KktcMeydan\AdsManager\Ad;
use KktcMeydan\AdsManager\AdUrlValidator;
use KktcMeydan\AdsManager\Api\Serializer\AdSerializer;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class UpdateAdController extends AbstractShowController
{
    public $serializer = AdSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);

        if (! $actor->isAdmin()) {
            throw new PermissionDeniedException;
        }

        $id = (int) Arr::get($request->getQueryParams(), 'id');
        $ad = Ad::findOrFail($id);

        $attributes = (array) Arr::get($request->getParsedBody(), 'data.attributes', []);

        if (array_key_exists('imageUrl', $attributes)) {
            AdUrlValidator::assertValid((string) $attributes['imageUrl'], 'imageUrl');
        }

        if (array_key_exists('targetUrl', $attributes)) {
            AdUrlValidator::assertValid((string) $attributes['targetUrl'], 'targetUrl');
        }

        foreach ([
            'title' => 'title',
            'imageUrl' => 'image_url',
            'targetUrl' => 'target_url',
            'position' => 'position',
        ] as $jsonKey => $column) {
            if (array_key_exists($jsonKey, $attributes)) {
                $ad->$column = (string) $attributes[$jsonKey];
            }
        }

        foreach ([
            'targetCategorySlug' => 'target_category_slug',
            'targetDistrictSlug' => 'target_district_slug',
            'targetUniversitySlug' => 'target_university_slug',
        ] as $jsonKey => $column) {
            if (array_key_exists($jsonKey, $attributes)) {
                $ad->$column = $attributes[$jsonKey] ?: null;
            }
        }

        if (array_key_exists('isActive', $attributes)) {
            $ad->is_active = (bool) $attributes['isActive'];
        }

        $ad->save();

        return $ad;
    }
}
