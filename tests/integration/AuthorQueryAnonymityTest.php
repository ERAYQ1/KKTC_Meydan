<?php

namespace KktcMeydan\Tests\integration;

/**
 * F1 regresyonu: yazar bazli sorgu yollari anonim icerigi ele vermemeli.
 *
 * Denetimdeki en agir bulgunun testi. `AnonymousMasker` yanittaki `user`
 * iliskisini null'luyordu, ama sorgunun KENDISI yazara gore filtrelenince
 * sonuc kumesi zaten kimligi ele veriyordu: `filter[author]=X` ile donen her
 * kayit tanimi geregi X'e ait.
 *
 * Uc ayri giris noktasi var, ucu de kapali olmali:
 *   - posts filterer       -> /api/posts?filter[author]=X   (profil sayfasi)
 *   - discussions filterer -> /api/discussions?filter[author]=X
 *   - discussions searcher -> /api/discussions?filter[q]=author:X
 *
 * Ucuncusu kritik: `filter[q]` geldiginde controller filterer'i degil
 * searcher'i cagirir, yani sadece filtre tarafini yamalamak arama yolunu
 * acik birakir.
 */
class AuthorQueryAnonymityTest extends AnonymityTestCase
{
    /** @test */
    public function guest_post_author_filtresinde_anonim_gonderiyi_goremez()
    {
        $ids = $this->dataIds($this->json(
            $this->apiGet('/api/posts', ['filter' => ['author' => 'anonyazar']])
        ));

        $this->assertNotContains(self::ANON_POST, $ids, 'Anonim gonderi yazar filtresinde gorundu.');
    }

    /** @test */
    public function ayni_filtre_yazarin_acik_gonderisini_hala_donduruyor()
    {
        // Kontrol testi: yama "her seyi gizle" seklinde asiri genis olsaydi
        // bu da kirilirdi. Anonim olmayan icerik etkilenmemeli.
        $ids = $this->dataIds($this->json(
            $this->apiGet('/api/posts', ['filter' => ['author' => 'anonyazar']])
        ));

        $this->assertContains(self::OPEN_POST, $ids, 'Acik kimlikli gonderi de gizlenmis - yama fazla genis.');
    }

    /** @test */
    public function guest_discussion_author_filtresinde_anonim_konuyu_goremez()
    {
        $ids = $this->dataIds($this->json(
            $this->apiGet('/api/discussions', ['filter' => ['author' => 'anonyazar']])
        ));

        $this->assertNotContains(self::ANON_DISCUSSION, $ids, 'Anonim konu yazar filtresinde gorundu.');
        $this->assertContains(self::OPEN_DISCUSSION, $ids, 'Acik konu da gizlenmis - yama fazla genis.');
    }

    /** @test */
    public function guest_arama_gambitinde_anonim_konuyu_goremez()
    {
        $ids = $this->dataIds($this->json(
            $this->apiGet('/api/discussions', ['filter' => ['q' => 'author:anonyazar']])
        ));

        $this->assertNotContains(self::ANON_DISCUSSION, $ids, 'Anonim konu arama gambitinde gorundu.');
    }

    /** @test */
    public function negatif_author_filtresi_de_anonim_icerigi_sizdirmiyor()
    {
        // `filter[-author]` ile "X'in disindakiler" alinip filtresiz listeyle
        // karsilastirilirsa X'in anonim konulari cikarim yoluyla bulunabilirdi.
        $ids = $this->dataIds($this->json(
            $this->apiGet('/api/discussions', ['filter' => ['-author' => 'anonyazar']])
        ));

        $this->assertNotContains(self::ANON_DISCUSSION, $ids, 'Anonim konu negatif yazar filtresinde gorundu.');
    }

    /** @test */
    public function viewIpsPosts_izni_olan_moderator_anonim_icerigi_yazara_gore_sorgulayabilir()
    {
        $ids = $this->dataIds($this->json(
            $this->apiGet('/api/discussions', ['filter' => ['author' => 'anonyazar']], self::MODERATOR)
        ));

        $this->assertContains(
            self::ANON_DISCUSSION,
            $ids,
            'Moderator anonim konuyu goremiyor - moderasyon yolu bozuldu.'
        );
    }
}
