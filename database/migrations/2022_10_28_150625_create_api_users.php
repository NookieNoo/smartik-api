<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up () {
        Schema::create('api_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id')->index();
            $table->string('name')->nullable();
            $table->string('token')->nullable();
            $table->string('http_parser')->nullable();

            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down () {
        Schema::dropIfExists('api_users');
    }
};