<?php

namespace KktcMeydan\BlockUser\Api\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Api\Serializer\UserSerializer;
use Flarum\Http\RequestUtil;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\UserRepository;
use Illuminate\Support\Arr;
use KktcMeydan\BlockUser\UserBlock;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class BlockUserController extends AbstractShowController
{
    public $serializer = UserSerializer::class;

    /**
     * @var UserRepository
     */
    private $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        $id = (int) Arr::get($request->getQueryParams(), 'id');
        $user = $this->users->findOrFail($id, $actor);

        if ($user->id === $actor->id) {
            throw new PermissionDeniedException('Kendinizi engelleyemezsiniz.');
        }

        UserBlock::block($actor->id, $user->id);

        return $user;
    }
}
