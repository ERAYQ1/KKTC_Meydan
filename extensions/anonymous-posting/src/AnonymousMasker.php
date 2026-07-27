<?php

namespace KktcMeydan\AnonymousPosting;

use Flarum\Discussion\Discussion;
use Flarum\Notification\Notification;
use Flarum\Post\Post;
use Illuminate\Support\Collection;

/**
 * Anonim discussion/post'lardan kimlik tasiyan TUM iliskileri serialize
 * edilmeden once soker. Moderatorler gercek kimligi ayrica, `viewIpsPosts`
 * iznine bagli `anonymousModLabel` attribute'undan alir.
 *
 * Onemli: sadece `user` iliskisini null'lamak yetmiyordu. Kimlik su
 * yollardan da siziyordu:
 *
 *   Post uzerinde       -> user, editedUser, hiddenUser
 *   Discussion uzerinde -> user, lastPostedUser ve GOMULU POST'lar:
 *                          firstPost / lastPost / mostRelevantPost / posts
 *                          (her biri kendi user/editedUser/hiddenUser'iyla)
 *   Notification uzerinde -> fromUser (abone olunan anonim konuya gelen
 *                            "yeni yanit" bildirimi yazari ele veriyordu)
 *
 * `mostRelevantPost` + `mostRelevantPost.user` ListDiscussionsController'in
 * VARSAYILAN include listesinde (opt-in degil), yani her aramada yukleniyor.
 */
class AnonymousMasker
{
    /** Bir Post uzerinde yazari ele veren iliskiler. */
    private const POST_IDENTITY_RELATIONS = ['user', 'editedUser', 'hiddenUser'];

    /** Bir Discussion'in icine gomulu gelebilen tekil Post iliskileri. */
    private const DISCUSSION_POST_RELATIONS = ['firstPost', 'lastPost', 'mostRelevantPost'];

    /**
     * `prepareDataForSerialization` callback'i. Controller ne dondururse
     * dondursun (tek model, koleksiyon, ya da iliski tasiyan bir model)
     * icindeki maskelenebilir her seyi bulur.
     */
    public static function mask($controller, $data): void
    {
        foreach (self::flatten($data) as $model) {
            self::maskModel($model);
        }
    }

    private static function maskModel($model): void
    {
        if ($model instanceof Post) {
            self::maskPost($model);
        } elseif ($model instanceof Discussion) {
            self::maskDiscussion($model);
        } elseif ($model instanceof Notification) {
            self::maskNotification($model);
        }
    }

    private static function maskPost(Post $post): void
    {
        if (! $post->is_anonymous) {
            return;
        }

        self::stripIdentity($post);
    }

    private static function stripIdentity(Post $post): void
    {
        foreach (self::POST_IDENTITY_RELATIONS as $relation) {
            $post->setRelation($relation, null);
        }
    }

    private static function maskDiscussion(Discussion $discussion): void
    {
        // Gomulu post'lar konunun kendisi anonim olmasa da anonim olabilir
        // (ornegin acik bir konudaki anonim yanit `lastPost` olarak gelir),
        // bu yuzden konu bayragindan BAGIMSIZ olarak her zaman gezilir.
        foreach (self::DISCUSSION_POST_RELATIONS as $relation) {
            $post = self::loadedRelation($discussion, $relation);

            if (! $post instanceof Post) {
                continue;
            }

            self::maskPost($post);

            // Konunun son gonderisi anonimse `lastPostedUser` o gonderinin
            // yazaridir - bu iliskiyi ayrica sokmek gerekir.
            if ($relation === 'lastPost' && $post->is_anonymous) {
                $discussion->setRelation('lastPostedUser', null);
            }
        }

        // ShowDiscussionController konuyu yuklu post akisiyla birlikte dondurur.
        // DIKKAT: burada `Collection` degil DUZ BIR ARRAY gelir ve icerigi
        // karisiktir - o sayfada yuklenen gonderiler icin Post modeli, geri
        // kalan akis icin sadece post id'si (bkz. ShowDiscussionController::
        // includePosts -> setRelation('posts', $allPosts)). Bu yuzden hem
        // array hem Collection kabul edilir, Post olmayan ogeler atlanir.
        $posts = self::loadedRelation($discussion, 'posts');

        if ($posts instanceof Collection || is_array($posts)) {
            foreach ($posts as $post) {
                if ($post instanceof Post) {
                    self::maskPost($post);
                }
            }
        }

        if (! $discussion->is_anonymous) {
            return;
        }

        $discussion->setRelation('user', null);

        // Konu listesi endpoint'inde `lastPost` YUKLENMEZ - sadece
        // `lastPostedUser` gelir, yani yukaridaki dongu bu iliskiyi hic
        // gormez. Bayragi ham sutunlardan cikariyoruz: anonim bir konunun
        // son gonderisi de yazarina aitse, o kullanici anonim yazardir.
        //
        // Son gonderi BASKASINA aitse (anonim konuya gelen acik kimlikli
        // yanit) iliski bilerek korunur - o kisi anonim degil.
        if ((int) $discussion->last_posted_user_id === (int) $discussion->user_id) {
            $discussion->setRelation('lastPostedUser', null);
        }

        // Anonim konunun ilk gonderisi tanimi geregi ayni kisiye ait. Bayrak
        // bir sebeple yazilmamis olsa bile (eski seed verisi gibi) konu
        // anonimse ilk gonderinin yazari acilmamali - fail-closed.
        $firstPost = self::loadedRelation($discussion, 'firstPost');

        if ($firstPost instanceof Post) {
            self::stripIdentity($firstPost);
        }
    }

    private static function maskNotification(Notification $notification): void
    {
        $subject = self::loadedRelation($notification, 'subject');

        if ($subject === null) {
            return;
        }

        self::maskModel($subject);

        $isAnonymousSubject = ($subject instanceof Post || $subject instanceof Discussion)
            && $subject->is_anonymous;

        if ($isAnonymousSubject) {
            $notification->setRelation('fromUser', null);
        }
    }

    /**
     * Iliskiyi SADECE zaten yuklenmisse dondurur. `$model->relation` ile
     * okumak tembel yukleme tetikler; bu hem gereksiz sorgu acar hem de
     * controller'in hic gondermeyecegi iliskileri bosuna doldurur.
     */
    private static function loadedRelation($model, string $relation)
    {
        return $model->relationLoaded($relation) ? $model->getRelation($relation) : null;
    }

    /**
     * Controller verisini maskelenebilir model listesine duzler.
     */
    private static function flatten($data): iterable
    {
        if ($data instanceof Post || $data instanceof Discussion || $data instanceof Notification) {
            return [$data];
        }

        if ($data instanceof Collection || is_array($data)) {
            return $data;
        }

        return [];
    }
}
