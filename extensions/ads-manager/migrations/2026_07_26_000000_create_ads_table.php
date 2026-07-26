<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable('ads', function (Blueprint $table) {
    $table->increments('id');
    $table->string('title', 255);
    $table->string('image_url', 500);
    $table->string('target_url', 500);
    $table->string('target_category_slug', 100)->nullable();
    $table->string('target_district_slug', 100)->nullable();
    $table->string('target_university_slug', 100)->nullable();
    $table->string('position', 50)->default('discussion_list');
    $table->boolean('is_active')->default(true);
    $table->unsignedInteger('impressions_count')->default(0);
    $table->unsignedInteger('clicks_count')->default(0);
    $table->timestamps();
});
