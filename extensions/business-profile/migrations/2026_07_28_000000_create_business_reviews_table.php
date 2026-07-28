<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable('business_reviews', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('business_user_id');
    $table->unsignedInteger('reviewer_user_id');
    $table->unsignedTinyInteger('rating');
    $table->text('comment')->nullable();
    $table->timestamps();

    $table->unique(['business_user_id', 'reviewer_user_id']);
    $table->foreign('business_user_id')->references('id')->on('users')->onDelete('cascade');
    $table->foreign('reviewer_user_id')->references('id')->on('users')->onDelete('cascade');
});
