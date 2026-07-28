<?php

namespace KktcMeydan\BusinessProfile\Api\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\User;
use Illuminate\Support\Arr;
use KktcMeydan\BusinessProfile\BusinessGroupGate;
use KktcMeydan\BusinessProfile\BusinessReview;
use KktcMeydan\BusinessProfile\Api\Serializer\BusinessReviewSerializer;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreateBusinessReviewController extends AbstractCreateController
{
    public $serializer = BusinessReviewSerializer::class;

    public $include = ['reviewer'];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);

        if (! $actor->exists) {
            throw new PermissionDeniedException;
        }

        $attributes = (array) Arr::get($request->getParsedBody(), 'data.attributes', []);
        $businessUserId = (int) Arr::get($attributes, 'businessUserId');
        $rating = (int) Arr::get($attributes, 'rating');
        $comment = Arr::get($attributes, 'comment');
        $comment = $comment === null ? null : (string) $comment;

        if ($businessUserId === $actor->id) {
            throw new ValidationException(['businessUserId' => 'Kendi isletmenizi degerlendiremezsiniz.']);
        }

        if ($rating < 1 || $rating > 5) {
            throw new ValidationException(['rating' => 'Puan 1 ile 5 arasinda olmalidir.']);
        }

        $business = User::find($businessUserId);

        if (! $business || ! BusinessGroupGate::isBusinessUser($business)) {
            throw new ValidationException(['businessUserId' => 'Gecerli bir isletme bulunamadi.']);
        }

        $review = BusinessReview::where('business_user_id', $businessUserId)
            ->where('reviewer_user_id', $actor->id)
            ->first() ?? new BusinessReview;

        $review->business_user_id = $businessUserId;
        $review->reviewer_user_id = $actor->id;
        $review->rating = $rating;
        $review->comment = $comment;
        $review->save();

        return $review;
    }
}
