<?php

namespace KktcMeydan\AnonymousPosting;

use Flarum\User\User;
use Illuminate\Database\Query\Builder;

/**
 * Tek yetki noktasi: "bu aktor anonim icerigin gercek yazarini gorebilir mi?"
 *
 * Cekirdegin hazir `discussion.viewIpsPosts` iznini kullaniyoruz - ayni izin
 * zaten `anonymousModLabel` serializer attribute'unu da aciyor (bkz.
 * extend.php), yani "kimligi gorebilen" tanimi tum extension boyunca tek.
 */
class AnonymityGuard
{
    const PERMISSION = 'discussion.viewIpsPosts';

    public static function canSeeRealAuthors(?User $actor): bool
    {
        return $actor !== null && $actor->hasPermission(self::PERMISSION);
    }

    /**
     * Yazara gore daraltilmis bir sorguya anonim satirlari disarida birakan
     * kosulu ekler.
     *
     * Neden gerekli: `AnonymousMasker` yanittaki `user` iliskisini null'luyor,
     * ama sorgunun KENDISI yazara gore filtrelenmisse sonuc kumesi zaten
     * "bu kullanicinin gonderileri" demektir - iliskiyi gizlemek kimligi
     * gizlemez. Bu yuzden yazar bazli her sorgu yolunda anonim satirlar
     * tamamen elenmeli.
     *
     * Negatif filtrede (`filter[-author]`) de uygulaniyor: aksi halde
     * "X'in disindakiler" sonucu filtresiz listeyle karsilastirilarak X'in
     * anonim gonderileri cikarim yoluyla bulunabilir.
     *
     * @param string $table 'posts' veya 'discussions'
     */
    public static function excludeAnonymous(Builder $query, ?User $actor, string $table): void
    {
        if (self::canSeeRealAuthors($actor)) {
            return;
        }

        $query->where($table.'.is_anonymous', false);
    }
}
