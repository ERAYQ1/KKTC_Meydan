<?php

namespace KktcMeydan\AnonymousPosting\Filter;

use Flarum\Filter\FilterState;
use Flarum\Post\Filter\AuthorFilter;
use KktcMeydan\AnonymousPosting\AnonymityGuard;

/**
 * Cekirdegin `GET /api/posts?filter[author]=X` filtresini anonimlik korumasi
 * ekleyerek degistirir (bkz. AnonymityQueryProvider - container'da takas
 * ediliyor, cekirdek sinifin yerine bu kaydediliyor).
 *
 * Bu yol Flarum'un kendi kullanici profili sayfasidir
 * (PostsUserPage.tsx -> filter[author] + filter[type]=comment), yani gizli
 * bir API hilesi degil, normal arayuzun kullandigi endpoint.
 */
class AnonymitySafeAuthorFilter extends AuthorFilter
{
    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        parent::filter($filterState, $filterValue, $negate);

        AnonymityGuard::excludeAnonymous(
            $filterState->getQuery(),
            $filterState->getActor(),
            'posts'
        );
    }
}
