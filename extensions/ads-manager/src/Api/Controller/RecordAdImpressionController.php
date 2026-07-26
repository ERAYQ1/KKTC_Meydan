<?php

namespace KktcMeydan\AdsManager\Api\Controller;

use Illuminate\Support\Arr;
use KktcMeydan\AdsManager\Ad;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RecordAdImpressionController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int) Arr::get($request->getQueryParams(), 'id');

        Ad::where('id', $id)->increment('impressions_count');

        return new EmptyResponse(204);
    }
}
