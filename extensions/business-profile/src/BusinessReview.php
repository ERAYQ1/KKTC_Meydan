<?php

namespace KktcMeydan\BusinessProfile;

use Flarum\Database\AbstractModel;
use Flarum\User\User;

/**
 * @property int $id
 * @property int $business_user_id
 * @property int $reviewer_user_id
 * @property int $rating
 * @property string|null $comment
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class BusinessReview extends AbstractModel
{
    protected $table = 'business_reviews';

    protected $casts = [
        'rating' => 'int',
    ];

    public $timestamps = true;

    public function business()
    {
        return $this->belongsTo(User::class, 'business_user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }
}
