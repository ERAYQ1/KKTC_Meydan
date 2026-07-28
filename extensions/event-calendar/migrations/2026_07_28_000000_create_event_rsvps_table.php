<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable('event_rsvps', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('discussion_id');
    $table->unsignedInteger('user_id');
    $table->string('status', 20)->default('going');
    $table->timestamp('reminded_at')->nullable();
    $table->timestamps();

    $table->unique(['discussion_id', 'user_id']);
    $table->foreign('discussion_id')->references('id')->on('discussions')->onDelete('cascade');
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
});
