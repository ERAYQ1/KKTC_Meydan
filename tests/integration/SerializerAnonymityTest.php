<?php

namespace KktcMeydan\Tests\integration;

/**
 * F2/F3 regresyonu: anonim icerigin serialize edilen ILISKILERI kimligi
 * ele vermemeli.
 *
 * Eski `AnonymousMasker` sadece modelin kendi `user` iliskisini null'luyordu.
 * Kimlik su yollardan siziyordu:
 *   - Discussion koleksiyonunda gomulu `mostRelevantPost.user`
 *     (ListDiscussionsController'in VARSAYILAN include'u - opt-in degil)
 *   - ShowDiscussionController'in `posts` iliskisi (Collection degil DUZ ARRAY
 *     dondugu icin eski kod hic gezmiyordu)
 *   - Post uzerindeki `editedUser` / `hiddenUser`
 *   - Discussion uzerindeki `lastPostedUser`
 */
class SerializerAnonymityTest extends AnonymityTestCase
{
    // NOT: asagidaki "included" testleri ANON_ONLY_AUTHOR kullaniyor.
    // ANON_AUTHOR ayni zamanda acik bir konunun da yazari oldugu icin onun
    // included blogunda gorunmesi dogru davranistir - sizinti degil.

    /** @test */
    public function konu_listesi_anonim_yazari_included_blogunda_sizdirmiyor()
    {
        $json = $this->json($this->apiGet('/api/discussions'));

        $this->assertFalse(
            $this->includesUser($json, self::ANON_ONLY_AUTHOR),
            'Konu listesinde anonim yazar included blogunda gorundu.'
        );
    }

    /** @test */
    public function konu_listesi_acik_konularin_yazarini_hala_gosteriyor()
    {
        // Kontrol testi: liste tamamen yazarsiz donuyorsa yukaridaki test
        // bos gecerdi. Acik konunun yazari included'da OLMALI.
        $json = $this->json($this->apiGet('/api/discussions'));

        $this->assertTrue(
            $this->includesUser($json, self::ANON_AUTHOR),
            'Acik konunun yazari da gizlenmis - maskeleme fazla genis.'
        );
    }

    /*
     * `mostRelevantPost` maskelemesi burada TEST EDILMIYOR - bilerek.
     *
     * O iliski yalnizca `filter[q]` ile arama yapildiginda doluyor, arama da
     * MariaDB InnoDB FULLTEXT (`MATCH ... AGAINST`) kullaniyor. Test harness
     * her testi bir transaction'a sarip fikstur satirlarini ayni transaction
     * icinde INSERT ediyor; henuz commit edilmemis satirlar uzerinde FULLTEXT
     * sorgusu calistirmak bu MariaDB surumunde (10.11.18) sunucuyu
     * dusuruyor - dogrulandi: test tam bu noktada mariadbd'yi oldururuyor ve
     * sonraki tum testler baglanti hatasiyla zincirleme dusuyor.
     *
     * Kapsam kaybi yok, iki yerden karsilaniyor:
     *   - Maskeleme mantigi: tests/unit/AnonymousMaskerTest.php
     *     (`mostRelevantPost` iliskisi elle yuklenip dogrulaniyor, DB yok)
     *   - Gercek arama yolu: scripts/verify-anonymity.php kontrol 4
     *     (calisan siteye karsi, transaction disinda - bu hatayi tetiklemiyor)
     */

    /** @test */
    public function konu_detayi_gonderi_akisinda_yazari_sizdirmiyor()
    {
        // ShowDiscussionController `posts` iliskisini duz array olarak set
        // eder (yuklenen Post modelleri + yuklenmeyen post id'leri karisik).
        $json = $this->json($this->apiGet('/api/discussions/'.self::ANON_ONLY_DISCUSSION));

        $this->assertFalse(
            $this->includesUser($json, self::ANON_ONLY_AUTHOR),
            'Konu detayindaki gonderi akisi anonim yazari sizdirdi.'
        );
    }

    /** @test */
    public function anonim_gonderinin_user_iliskisi_null()
    {
        $json = $this->json($this->apiGet('/api/posts/'.self::ANON_POST));

        $this->assertNull(
            $json['data']['relationships']['user']['data'] ?? null,
            'Anonim gonderinin user iliskisi dolu geldi.'
        );
    }

    /** @test */
    public function acik_kimlikli_gonderinin_yazari_hala_goruluyor()
    {
        // Kontrol testi: maskeleme asiri genis olmamali.
        $json = $this->json($this->apiGet('/api/posts/'.self::OPEN_POST));

        $this->assertSame(
            (string) self::ANON_AUTHOR,
            $json['data']['relationships']['user']['data']['id'] ?? null,
            'Acik kimlikli gonderinin yazari da gizlenmis - maskeleme fazla genis.'
        );
    }

    /** @test */
    public function anonim_konunun_lastPostedUser_iliskisi_null()
    {
        $json = $this->json($this->apiGet('/api/discussions/'.self::ANON_DISCUSSION, [
            'include' => 'lastPostedUser',
        ]));

        $this->assertNull(
            $json['data']['relationships']['lastPostedUser']['data'] ?? null,
            'Anonim konunun lastPostedUser iliskisi yazari sizdirdi.'
        );
    }

    /** @test */
    public function moderator_anonymousModLabel_ile_gercek_kimligi_hala_goruyor()
    {
        // Maskeleme moderasyon yolunu kapatmamali: `viewIpsPosts` izni olan
        // kullanici gercek kimligi ayri attribute'tan almaya devam etmeli.
        $json = $this->json($this->apiGet('/api/posts/'.self::ANON_POST, [], self::MODERATOR));

        $label = $json['data']['attributes']['anonymousModLabel'] ?? null;

        $this->assertNotNull($label, 'Moderator anonymousModLabel alamadi.');
        $this->assertStringContainsString('anonyazar', $label, 'Etiket gercek kullanici adini icermiyor.');
    }

    /** @test */
    public function guest_anonymousModLabel_alamiyor()
    {
        $json = $this->json($this->apiGet('/api/posts/'.self::ANON_POST));

        $this->assertNull(
            $json['data']['attributes']['anonymousModLabel'] ?? null,
            'Guest anonymousModLabel gordu - izin kapisi calismiyor.'
        );
    }
}
