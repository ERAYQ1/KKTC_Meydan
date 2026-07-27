<?php

namespace KktcMeydan\AnonymousPosting\Provider;

use Flarum\Discussion\Filter\DiscussionFilterer;
use Flarum\Discussion\Query\AuthorFilterGambit;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Post\Filter\AuthorFilter;
use Flarum\Post\Filter\PostFilterer;
use KktcMeydan\AnonymousPosting\Filter\AnonymitySafeAuthorFilter;
use KktcMeydan\AnonymousPosting\Query\AnonymitySafeAuthorFilterGambit;

/**
 * Cekirdegin yazar-bazli sorgu siniflarini anonimlik korumali alt siniflarla
 * takas eder.
 *
 * `Extend\Filter` sadece filtre EKLEYEBILIYOR (removeFilter yok) ve arama
 * gambit'lerine hic dokunmuyor; bu yuzden ilgili container binding'leri
 * dogrudan genisletiliyor.
 */
class AnonymityQueryProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->extend('flarum.filter.filters', function (array $filters) {
            $filters[PostFilterer::class] = self::swap(
                $filters[PostFilterer::class] ?? [],
                AuthorFilter::class,
                AnonymitySafeAuthorFilter::class
            );

            $filters[DiscussionFilterer::class] = self::swap(
                $filters[DiscussionFilterer::class] ?? [],
                AuthorFilterGambit::class,
                AnonymitySafeAuthorFilterGambit::class
            );

            return $filters;
        });

        $this->container->extend('flarum.simple_search.gambits', function (array $gambits) {
            $gambits[DiscussionSearcher::class] = self::swap(
                $gambits[DiscussionSearcher::class] ?? [],
                AuthorFilterGambit::class,
                AnonymitySafeAuthorFilterGambit::class
            );

            return $gambits;
        });
    }

    /**
     * $needle'i $replacement ile degistirir. Cekirdek sinifi listede
     * bulamazsa (surum degisikligi, baska bir extension onu zaten
     * degistirmis olabilir) replacement'i yine de ekler - koruma
     * sessizce dusmesin diye fail-closed davraniyoruz.
     */
    private static function swap(array $classes, string $needle, string $replacement): array
    {
        $index = array_search($needle, $classes, true);

        if ($index === false) {
            if (! in_array($replacement, $classes, true)) {
                $classes[] = $replacement;
            }

            return $classes;
        }

        $classes[$index] = $replacement;

        return array_values($classes);
    }
}
