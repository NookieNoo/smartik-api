<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::create('kkm_checks', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->unsignedBigInteger('order_id')->index();
            $table->enum('type', ['hold', 'final', 'cancel'])->default('hold')->index();
            $table->json('checks')->nullable();
            $table->json('extra')->nullable();

            $table->timestamps();
        });
    }

    public function down ()
    {
        Schema::dropIfExists('kkm_checks');
    }
};