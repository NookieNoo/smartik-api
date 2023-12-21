<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::create('user_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->boolean('default')->default(false);
            $table->unsignedMediumInteger('user_id')->index();
            $table->string('name')->nullable();
            $table->enum('mehtod', ['creditcard', 'sbp'])->default('creditcard');
            $table->json('data')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down ()
    {
        Schema::dropIfExists('user_payments');
    }
};