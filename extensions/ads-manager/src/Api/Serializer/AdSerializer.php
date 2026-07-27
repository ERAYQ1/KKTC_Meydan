<?php

namespace KktcMeydan\AdsManager\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use KktcMeydan\AdsManager\Ad;

class AdSerializer extends AbstractSerializer
{
    protected $type = 'ads';

    protected function getDefaultAttributes($ad)
    {
        /** @var Ad $ad */
        return [
            'title' => $ad->title,
            'imageUrl' => $ad->image_url,
            'targetUrl' => $ad->target_url,
            'targetCategorySlug' => $ad->target_category_slug,
            'targetDistrictSlug' => $ad->target_district_slug,
            'targetUniversitySlug' => $ad->target_university_slug,
            'isActive' => (bool) $ad->is_active,
            'impressionsCount' => $ad->impressions_count,
            'clicksCount' => $ad->clicks_count,
            'createdAt' => $this->formatDate($ad->created_at),
        ];
    }
}
