<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        if ($schema->hasTable('user_blocks')) {
            return;
        }

        $schema->create('user_blocks', function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->integer('blocked_user_id')->unsigned();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('blocked_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->primary(['user_id', 'blocked_user_id']);
        });
    },
    'down' => function (Builder $schema) {
        $schema->dropIfExists('user_blocks');
    },
];
