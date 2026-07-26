<?php

namespace KktcMeydan\AnalyticsDashboard\Api\Controller;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Http\RequestUtil;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\User;
use Illuminate\Database\ConnectionInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ShowAnalyticsSummaryController implements RequestHandlerInterface
{
    const POPULAR_TAGS_LIMIT = 8;

    /**
     * @var ConnectionInterface
     */
    private $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);

        if (! $actor->isAdmin()) {
            throw new PermissionDeniedException;
        }

        $now = Carbon::now();

        return new JsonResponse([
            'totalDiscussions' => Discussion::query()->count(),
            'totalPosts' => Post::query()->count(),
            'totalUsers' => User::query()->count(),
            'dau' => User::query()->where('last_seen_at', '>=', $now->copy()->startOfDay())->count(),
            'wau' => User::query()->where('last_seen_at', '>=', $now->copy()->subDays(7))->count(),
            'popularByDiscussions' => $this->popularByDiscussions(),
            'popularByPosts' => $this->popularByPosts(),
        ]);
    }

    private function popularByDiscussions(): array
    {
        return Tag::query()
            ->orderByDesc('discussion_count')
            ->limit(self::POPULAR_TAGS_LIMIT)
            ->get(['name', 'slug', 'discussion_count'])
            ->map(function (Tag $tag) {
                return [
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                    'count' => $tag->discussion_count,
                ];
            })
            ->all();
    }

    private function popularByPosts(): array
    {
        return $this->db->table('discussion_tag')
            ->join('discussions', 'discussions.id', '=', 'discussion_tag.discussion_id')
            ->join('tags', 'tags.id', '=', 'discussion_tag.tag_id')
            ->select('tags.name', 'tags.slug', $this->db->raw('SUM(discussions.comment_count) as total_posts'))
            ->groupBy('tags.id', 'tags.name', 'tags.slug')
            ->orderByDesc('total_posts')
            ->limit(self::POPULAR_TAGS_LIMIT)
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->name,
                    'slug' => $row->slug,
                    'count' => (int) $row->total_posts,
                ];
            })
            ->all();
    }
}
