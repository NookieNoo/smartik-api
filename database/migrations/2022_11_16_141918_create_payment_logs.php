<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->unsignedBigInteger('payment_id')->index();
            $table->boolean('success')->default(false);
            $table->boolean('verify')->default(false);
            $table->enum('type', ['webhook', 'ping'])->nullable();
            $table->json('data')->nullable();

            $table->timestamps();
        });
    }

    public function down ()
    {
        Schema::dropIfExists('payment_logs');
    }
};