<?php

namespace KktcMeydan\Tests\unit;

use Flarum\Discussion\Discussion;
use Flarum\Notification\Notification;
use Flarum\Post\CommentPost;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Collection;
use KktcMeydan\AnonymousPosting\AnonymousMasker;
use PHPUnit\Framework\TestCase;

/**
 * `AnonymousMasker`'in iliski sokme mantigini veritabanina hic dokunmadan
 * dogrular. Iliskiler elle `setRelation` ile kuruluyor, yani her senaryo
 * (arama sonucundaki `mostRelevantPost` dahil) burada deterministik olarak
 * test edilebiliyor.
 *
 * Entegrasyon tarafinda arama yolu test EDILEMIYOR: MariaDB FULLTEXT sorgusu
 * commit edilmemis bir transaction icinde sunucuyu dusuruyor
 * (bkz. SerializerAnonymityTest'teki aciklama). Bu dosya o boslugu kapatiyor.
 */
class AnonymousMaskerTest extends TestCase
{
    private function user(int $id): User
    {
        $user = new User;
        $user->id = $id;
        $user->username = 'yazar'.$id;

        return $user;
    }

    private function post(int $id, bool $anonymous, int $userId = 1): CommentPost
    {
        $post = new CommentPost;
        $post->id = $id;
        $post->user_id = $userId;
        $post->is_anonymous = $anonymous;
        $post->setRelation('user', $this->user($userId));
        $post->setRelation('editedUser', $this->user($userId));
        $post->setRelation('hiddenUser', $this->user($userId));

        return $post;
    }

    private function discussion(int $id, bool $anonymous, int $userId = 1): Discussion
    {
        $discussion = new Discussion;
        $discussion->id = $id;
        $discussion->user_id = $userId;
        $discussion->last_posted_user_id = $userId;
        $discussion->is_anonymous = $anonymous;
        $discussion->setRelation('user', $this->user($userId));
        $discussion->setRelation('lastPostedUser', $this->user($userId));

        return $discussion;
    }

    public function test_anonim_gonderide_user_editedUser_hiddenUser_sokuluyor()
    {
        $post = $this->post(1, true);

        AnonymousMasker::mask(null, $post);

        $this->assertNull($post->getRelation('user'));
        $this->assertNull($post->getRelation('editedUser'), 'editedUser yazari sizdiriyor.');
        $this->assertNull($post->getRelation('hiddenUser'), 'hiddenUser yazari sizdiriyor.');
    }

    public function test_acik_gonderi_dokunulmadan_geciyor()
    {
        $post = $this->post(1, false);

        AnonymousMasker::mask(null, $post);

        $this->assertNotNull($post->getRelation('user'), 'Acik gonderi de maskelendi - kapsam fazla genis.');
    }

    public function test_arama_sonucundaki_mostRelevantPost_maskeleniyor()
    {
        // ListDiscussionsController'in VARSAYILAN include'u. Eski masker bir
        // Discussion koleksiyonunda `instanceof Post` arayip bos donuyor,
        // gomulu post'a hic inmiyordu - sizintinin tam kaynagi buydu.
        $discussion = $this->discussion(1, false);
        $discussion->setRelation('mostRelevantPost', $this->post(9, true, 7));

        AnonymousMasker::mask(null, new Collection([$discussion]));

        $this->assertNull(
            $discussion->getRelation('mostRelevantPost')->getRelation('user'),
            'mostRelevantPost.user anonim yazari sizdiriyor.'
        );
    }

    public function test_gomulu_firstPost_ve_lastPost_maskeleniyor()
    {
        $discussion = $this->discussion(1, false);
        $discussion->setRelation('firstPost', $this->post(9, true, 7));
        $discussion->setRelation('lastPost', $this->post(10, true, 7));

        AnonymousMasker::mask(null, $discussion);

        $this->assertNull($discussion->getRelation('firstPost')->getRelation('user'));
        $this->assertNull($discussion->getRelation('lastPost')->getRelation('user'));
        $this->assertNull(
            $discussion->getRelation('lastPostedUser'),
            'Son gonderi anonimken lastPostedUser aciк kaldi.'
        );
    }

    public function test_anonim_konuda_lastPostedUser_ham_sutunlardan_sokuluyor()
    {
        // Konu listesi endpoint'inde `lastPost` YUKLENMEZ; karar yalnizca
        // last_posted_user_id == user_id karsilastirmasindan verilebilir.
        $discussion = $this->discussion(1, true, 5);

        AnonymousMasker::mask(null, $discussion);

        $this->assertNull($discussion->getRelation('user'));
        $this->assertNull($discussion->getRelation('lastPostedUser'));
    }

    public function test_anonim_konuya_gelen_acik_yanitin_yazari_korunuyor()
    {
        // Anonim konu, ama son gonderi BASKASINA ait ve anonim degil:
        // o kisinin kimligi gizlenmemeli.
        $discussion = $this->discussion(1, true, 5);
        $discussion->last_posted_user_id = 99;
        $discussion->setRelation('lastPostedUser', $this->user(99));

        AnonymousMasker::mask(null, $discussion);

        $this->assertNull($discussion->getRelation('user'), 'Konu sahibi hala gorunuyor.');
        $this->assertNotNull(
            $discussion->getRelation('lastPostedUser'),
            'Acik kimlikli yanit sahibi gereksiz yere gizlendi.'
        );
    }

    public function test_ShowDiscussion_duz_array_posts_iliskisi_geziliyor()
    {
        // ShowDiscussionController `posts`'u Collection degil DUZ ARRAY olarak
        // set eder ve icinde Post modelleriyle birlikte ham post id'leri de
        // bulunur. Eski kod sadece Collection kontrol ettigi icin bu akisi
        // tamamen atliyordu - canli testte yakalanan gercek sizinti.
        $anonPost = $this->post(9, true, 7);

        $discussion = $this->discussion(1, true, 7);
        $discussion->setRelation('posts', [101, 102, $anonPost, 103]);

        AnonymousMasker::mask(null, $discussion);

        $this->assertNull($anonPost->getRelation('user'), 'Duz array icindeki anonim gonderi maskelenmedi.');
    }

    public function test_anonim_gonderi_bildiriminde_fromUser_sokuluyor()
    {
        $notification = new Notification;
        $notification->setRelation('subject', $this->post(9, true, 7));
        $notification->setRelation('fromUser', $this->user(7));

        AnonymousMasker::mask(null, $notification);

        $this->assertNull(
            $notification->getRelation('fromUser'),
            'Bildirimdeki fromUser anonim yaniti yazan kisiyi ele veriyor.'
        );
    }

    public function test_acik_gonderi_bildiriminde_fromUser_korunuyor()
    {
        $notification = new Notification;
        $notification->setRelation('subject', $this->post(9, false, 7));
        $notification->setRelation('fromUser', $this->user(7));

        AnonymousMasker::mask(null, $notification);

        $this->assertNotNull(
            $notification->getRelation('fromUser'),
            'Acik gonderi bildiriminde fromUser gereksiz yere silindi.'
        );
    }

    public function test_yuklenmemis_iliskiler_tembel_yukleme_tetiklemiyor()
    {
        // Iliskiye `$model->relation` ile erisilseydi Eloquent DB'ye giderdi.
        // Burada hicbir baglanti yok; sorgu denenirse test hata verir.
        $post = new CommentPost;
        $post->id = 1;
        $post->is_anonymous = true;

        AnonymousMasker::mask(null, $post);

        $this->assertFalse($post->relationLoaded('discussion'));
    }
}
