<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::create('kkm_logs', function (Blueprint $table) {
            $table->id();
            $table->json('request')->nullable();
            $table->unsignedSmallInteger('response_code');
            $table->json('response')->nullable();
            $table->timestamps();
        });
    }

    public function down ()
    {
        Schema::dropIfExists('kkm_logs');
    }
};