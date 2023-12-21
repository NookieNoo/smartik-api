<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::create('order_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->string('vehicle')->nullable();
            $table->string('gosnumber')->nullable();
            $table->json('extra')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('on_way_at')->nullable();
            $table->timestamp('in_radius_at')->nullable();
            $table->timestamp('arrival_at')->nullable();
            $table->timestamps();
        });
    }

    public function down ()
    {
        Schema::dropIfExists('order_deliveries');
    }
};