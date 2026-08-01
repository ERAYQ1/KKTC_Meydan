<?php

namespace KktcMeydan\BlockUser;

use Flarum\Database\AbstractModel;

/**
 * @property int            $user_id
 * @property int            $blocked_user_id
 * @property \Carbon\Carbon $created_at
 */
class UserBlock extends AbstractModel
{
    protected $table = 'user_blocks';

    public $timestamps = false;

    protected $fillable = ['user_id', 'blocked_user_id'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    private static array $stateCache = [];

    public static function block(int $userId, int $blockedUserId): void
    {
        static::query()->firstOrCreate([
            'user_id' => $userId,
            'blocked_user_id' => $blockedUserId,
        ], [
            'created_at' => \Carbon\Carbon::now(),
        ]);

        static::invalidateCache($userId, $blockedUserId);
    }

    public static function unblock(int $userId, int $blockedUserId): void
    {
        static::query()
            ->where('user_id', $userId)
            ->where('blocked_user_id', $blockedUserId)
            ->delete();

        static::invalidateCache($userId, $blockedUserId);
    }

    /**
     * Whether $userId has blocked $blockedUserId. Cached for the duration
     * of the request so serializing lists of users/posts doesn't N+1 query.
     */
    public static function isBlocked(int $userId, int $blockedUserId): bool
    {
        $key = $userId.':'.$blockedUserId;

        if (! array_key_exists($key, self::$stateCache)) {
            self::$stateCache[$key] = static::query()
                ->where('user_id', $userId)
                ->where('blocked_user_id', $blockedUserId)
                ->exists();
        }

        return self::$stateCache[$key];
    }

    public static function invalidateCache(int $userId, int $blockedUserId): void
    {
        unset(self::$stateCache[$userId.':'.$blockedUserId]);
    }

    /**
     * @return int[]
     */
    public static function blockedIdsFor(int $userId): array
    {
        return static::query()
            ->where('user_id', $userId)
            ->pluck('blocked_user_id')
            ->all();
    }
}
