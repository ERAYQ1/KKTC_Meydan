<?php

namespace KktcMeydan\AnonymousPosting\Query;

use Flarum\Discussion\Query\AuthorFilterGambit;
use Flarum\Filter\FilterState;
use Flarum\Search\SearchState;
use KktcMeydan\AnonymousPosting\AnonymityGuard;

/**
 * Cekirdegin discussion yazar sorgusunu anonimlik korumasiyla degistirir.
 *
 * Cekirdekteki `AuthorFilterGambit` AYNI ANDA iki ayri yolda kayitli:
 *   - `flarum.filter.filters[DiscussionFilterer]`  -> filter[author]=X
 *   - `flarum.simple_search.gambits[DiscussionSearcher]` -> filter[q]=author:X
 *
 * `filter[q]` geldiginde ListDiscussionsController filterer'i degil
 * searcher'i cagiriyor, yani sadece filter-mutator eklemek arama yolunu
 * ACIK BIRAKIR. Bu yuzden iki giris noktasi (`filter()` ve `conditions()`)
 * ayri ayri override ediliyor.
 */
class AnonymitySafeAuthorFilterGambit extends AuthorFilterGambit
{
    /** filter[author]=X yolu */
    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        parent::filter($filterState, $filterValue, $negate);

        AnonymityGuard::excludeAnonymous(
            $filterState->getQuery(),
            $filterState->getActor(),
            'discussions'
        );
    }

    /** filter[q]=author:X yolu */
    protected function conditions(SearchState $search, array $matches, $negate)
    {
        parent::conditions($search, $matches, $negate);

        AnonymityGuard::excludeAnonymous(
            $search->getQuery(),
            $search->getActor(),
            'discussions'
        );
    }
}
