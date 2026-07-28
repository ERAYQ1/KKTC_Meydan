<?php

namespace KktcMeydan\BusinessProfile\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\UserSerializer;
use KktcMeydan\BusinessProfile\BusinessReview;

class BusinessReviewSerializer extends AbstractSerializer
{
    protected $type = 'business-reviews';

    protected function getDefaultAttributes($review)
    {
        /** @var BusinessReview $review */
        return [
            'rating' => $review->rating,
            'comment' => $review->comment,
            'createdAt' => $this->formatDate($review->created_at),
        ];
    }

    protected function reviewer($review)
    {
        /** @var BusinessReview $review */
        return $this->hasOne($review, UserSerializer::class, 'reviewer');
    }
}
