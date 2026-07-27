<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/*
 * `position` was added for a future multi-placement banner system that was
 * never built - every ad forced to 'discussion_list', no UI ever let it be
 * changed, and nothing in the forum ever branched on its value. Dead column,
 * not a real feature; dropped rather than growing a selector UI for a
 * single hardcoded option.
 */
return [
    'up' => function (Builder $schema) {
        $schema->table('ads', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('ads', function (Blueprint $table) {
            $table->string('position', 50)->default('discussion_list');
        });
    },
];
