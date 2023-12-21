<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::create('provider_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id')->index();
            $table->string('external_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->timestamps();
        });
    }

    public function down ()
    {
        Schema::dropIfExists('provider_products');
    }
};