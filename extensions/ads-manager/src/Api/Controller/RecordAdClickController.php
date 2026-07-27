<?php

namespace KktcMeydan\AdsManager\Api\Controller;

use Illuminate\Support\Arr;
use KktcMeydan\AdsManager\Ad;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RecordAdClickController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int) Arr::get($request->getQueryParams(), 'id');

        Ad::findOrFail($id)->increment('clicks_count');

        return new EmptyResponse(204);
    }
}
