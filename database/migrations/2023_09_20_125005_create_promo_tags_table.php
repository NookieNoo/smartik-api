<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up (): void
    {
        Schema::create('promo_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tag_id')->index();
            $table->boolean('only_new_users')->default(false);
            $table->unsignedSmallInteger('max_uses')->default(0);
            $table->unsignedSmallInteger('max_uses_per_days')->default(0);
            $table->boolean('disable_minimum_sum')->default(false);
            $table->boolean('disable_delivery')->default(false);
            $table->boolean('active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down (): void
    {
        Schema::dropIfExists('promo_tags');
    }
};
