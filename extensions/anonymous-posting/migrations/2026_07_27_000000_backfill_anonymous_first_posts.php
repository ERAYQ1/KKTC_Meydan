<?php

use Illuminate\Database\Schema\Builder;

/**
 * Anonim konularin ilk gonderilerindeki eksik `is_anonymous` bayragini onarir.
 *
 * Gecmiste bayrak sadece `discussions` satirina yaziliyordu (bkz. seed.php'nin
 * eski anonim bolumu). Sonuc: konu listesinde "Anonim Uye" gorunuyor ama konu
 * acildiginda ilk gonderide gercek yazar cikiyordu. seed.php artik iki tarafa
 * da yaziyor, ancak mevcut satirlar geriye donuk duzeltilmeli - seed
 * idempotent oldugu icin var olan konulari atliyor ve kendi kendine onarmiyor.
 */
return [
    'up' => function (Builder $schema) {
        $schema->getConnection()
            ->table('posts')
            ->join('discussions', 'discussions.id', '=', 'posts.discussion_id')
            ->where('discussions.is_anonymous', true)
            ->where('posts.number', 1)
            ->update(['posts.is_anonymous' => true]);
    },

    // Geri alinamaz: bu migration'dan onceki hangi ilk gonderilerin zaten
    // dogru sekilde anonim isaretlendigini ayirt edecek bilgi yok. Bayragi
    // toptan geri almak gercek anonim gonderileri ifsa ederdi, o yuzden
    // down bilerek bos birakildi.
    'down' => function (Builder $schema) {
    },
];
