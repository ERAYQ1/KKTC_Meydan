<?php

namespace KktcMeydan\AdsManager;

use Flarum\Database\AbstractModel;

/**
 * @property int $id
 * @property string $title
 * @property string $image_url
 * @property string $target_url
 * @property string|null $target_category_slug
 * @property string|null $target_district_slug
 * @property string|null $target_university_slug
 * @property bool $is_active
 * @property int $impressions_count
 * @property int $clicks_count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Ad extends AbstractModel
{
    protected $table = 'ads';

    protected $casts = [
        'is_active' => 'bool',
        'impressions_count' => 'int',
        'clicks_count' => 'int',
    ];

    public $timestamps = true;
}
