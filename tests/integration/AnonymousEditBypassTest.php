<?php

namespace KktcMeydan\Tests\integration;

/**
 * F4 regresyonu: anonim gonderiyi duzenlerken icerik filtresi atlanmamali.
 *
 * Eski `SetPostAnonymousFlag`, istekte `isAnonymous` yoksa hemen donuyordu.
 * Atlatma su kadar basitti:
 *   1. Temiz icerikle anonim gonderi ac  -> filtre calisir, gecer
 *   2. Sadece `content` iceren PATCH at  -> `isAnonymous` yok, listener
 *      hemen doner, `is_anonymous` DB'de true kalir ama kufur/telefon
 *      filtresi HIC calismaz
 *
 * Artik bayrak gonderilmediginde gonderinin mevcut durumu esas aliniyor.
 */
class AnonymousEditBypassTest extends AnonymityTestCase
{
    /** @test */
    public function isAnonymous_gondermeden_yapilan_duzenlemede_kufur_filtresi_calisiyor()
    {
        $response = $this->send($this->request('PATCH', '/api/posts/'.self::ANON_POST, [
            'authenticatedAs' => self::ANON_AUTHOR,
            'json' => [
                'data' => [
                    'attributes' => [
                        // `isAnonymous` BILEREK yok - atlatmanin can damari.
                        'content' => 'siktir git buradan',
                    ],
                ],
            ],
        ]));

        $this->assertSame(
            422,
            $response->getStatusCode(),
            'Anonim gonderi duzenlemesinde kufur filtresi atlandi.'
        );
    }

    /** @test */
    public function temiz_icerikli_duzenleme_hala_kabul_ediliyor()
    {
        // Kontrol testi: filtre her duzenlemeyi reddediyorsa yukaridaki test
        // bos gecerdi.
        $response = $this->send($this->request('PATCH', '/api/posts/'.self::ANON_POST, [
            'authenticatedAs' => self::ANON_AUTHOR,
            'json' => [
                'data' => [
                    'attributes' => [
                        'content' => 'Guncellenmis, tamamen temiz bir metin.',
                    ],
                ],
            ],
        ]));

        $this->assertSame(
            200,
            $response->getStatusCode(),
            'Temiz icerikli duzenleme reddedildi.'
        );
    }

    /** @test */
    public function anonim_olmayan_gonderi_duzenlemesi_etkilenmiyor()
    {
        // Filtre kapsami yalnizca anonim icerik. Acik kimlikli gonderide
        // ayni metin gecmeli - kapsam genislemesi olmamali.
        $response = $this->send($this->request('PATCH', '/api/posts/'.self::OPEN_POST, [
            'authenticatedAs' => self::ANON_AUTHOR,
            'json' => [
                'data' => [
                    'attributes' => [
                        'content' => 'siktir git buradan',
                    ],
                ],
            ],
        ]));

        $this->assertSame(
            200,
            $response->getStatusCode(),
            'Anonim olmayan gonderi de anonim filtresine takildi.'
        );
    }
}
