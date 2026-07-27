<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/*
 * AdBanner's lookup filters on `is_active` and (optionally) the current
 * tag's `target_category_slug` on every discussion-list render - a
 * composite index lets both be satisfied by one index scan instead of a
 * table scan once the ads table grows past a handful of rows.
 */
return [
    'up' => function (Builder $schema) {
        $schema->table('ads', function (Blueprint $table) {
            $table->index(['is_active', 'target_category_slug'], 'ads_is_active_target_category_slug_index');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('ads', function (Blueprint $table) {
            $table->dropIndex('ads_is_active_target_category_slug_index');
        });
    },
];
