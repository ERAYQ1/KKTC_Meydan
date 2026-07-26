<?php

namespace KktcMeydan\AdsManager\Api\Controller;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Support\Arr;
use KktcMeydan\AdsManager\Ad;
use Psr\Http\Message\ServerRequestInterface;

class DeleteAdController extends AbstractDeleteController
{
    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);

        if (! $actor->isAdmin()) {
            throw new PermissionDeniedException;
        }

        $id = (int) Arr::get($request->getQueryParams(), 'id');

        Ad::findOrFail($id)->delete();
    }
}
