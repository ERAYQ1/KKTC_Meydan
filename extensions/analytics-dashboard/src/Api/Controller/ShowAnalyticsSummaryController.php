<?php

namespace KktcMeydan\AnalyticsDashboard\Api\Controller;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Http\RequestUtil;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
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

    // Both rolling windows ending "now", not one calendar-day-aligned and
    // one rolling - a DAU counted at 23:59 vs 00:01 used to swing wildly
    // under the old startOfDay() definition, and wasn't comparable to WAU's
    // rolling window.
    const DAU_WINDOW_HOURS = 24;
    const WAU_WINDOW_DAYS = 7;

    // `popularByPosts` joins discussion_tag+discussions+tags and sums
    // comment_count across ALL history on every admin dashboard load - on a
    // busy forum that's an unbounded, ever-growing aggregation. Bounded to a
    // recent window and cached, same as `popularByDiscussions` effectively
    // is via the already-materialized `discussion_count` column.
    const POPULAR_BY_POSTS_WINDOW_DAYS = 30;
    const POPULAR_BY_POSTS_CACHE_TTL_SECONDS = 900;
    const POPULAR_BY_POSTS_CACHE_KEY = 'kktcmeydan-analytics-dashboard.popular_by_posts_cache';

    /**
     * @var ConnectionInterface
     */
    private $db;

    /**
     * @var SettingsRepositoryInterface
     */
    private $settings;

    public function __construct(ConnectionInterface $db, SettingsRepositoryInterface $settings)
    {
        $this->db = $db;
        $this->settings = $settings;
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
            'dau' => User::query()->where('last_seen_at', '>=', $now->copy()->subHours(self::DAU_WINDOW_HOURS))->count(),
            'wau' => User::query()->where('last_seen_at', '>=', $now->copy()->subDays(self::WAU_WINDOW_DAYS))->count(),
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
        $cached = $this->settings->get(self::POPULAR_BY_POSTS_CACHE_KEY);

        if ($cached) {
            $decoded = json_decode($cached, true);

            if (
                is_array($decoded)
                && isset($decoded['generatedAt'], $decoded['data'])
                && Carbon::now()->timestamp - $decoded['generatedAt'] < self::POPULAR_BY_POSTS_CACHE_TTL_SECONDS
            ) {
                return $decoded['data'];
            }
        }

        $data = $this->computePopularByPosts();

        $this->settings->set(self::POPULAR_BY_POSTS_CACHE_KEY, json_encode([
            'generatedAt' => Carbon::now()->timestamp,
            'data' => $data,
        ]));

        return $data;
    }

    private function computePopularByPosts(): array
    {
        $since = Carbon::now()->subDays(self::POPULAR_BY_POSTS_WINDOW_DAYS);

        return $this->db->table('discussion_tag')
            ->join('discussions', 'discussions.id', '=', 'discussion_tag.discussion_id')
            ->join('tags', 'tags.id', '=', 'discussion_tag.tag_id')
            ->where('discussions.created_at', '>=', $since)
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
