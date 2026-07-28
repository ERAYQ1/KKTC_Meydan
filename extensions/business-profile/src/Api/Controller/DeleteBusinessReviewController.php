<?php

namespace KktcMeydan\BusinessProfile\Api\Controller;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Support\Arr;
use KktcMeydan\BusinessProfile\BusinessReview;
use Psr\Http\Message\ServerRequestInterface;

class DeleteBusinessReviewController extends AbstractDeleteController
{
    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);
        $id = (int) Arr::get($request->getQueryParams(), 'id');

        $review = BusinessReview::findOrFail($id);

        if ($review->reviewer_user_id !== $actor->id && ! $actor->isAdmin()) {
            throw new PermissionDeniedException;
        }

        $review->delete();
    }
}
