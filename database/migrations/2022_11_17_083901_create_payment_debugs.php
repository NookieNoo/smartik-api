<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::create('payment_debugs', function (Blueprint $table) {
            $table->id();
            $table->enum('system', ['lifepay'])->nullable()->index();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('payment_id')->index();
            $table->enum('side', ['in', 'out'])->default('out');
            $table->string('request_url')->nullable();
            $table->enum('request_method', ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'])->nullable();
            $table->json('request_headers')->nullable();
            $table->text('request_body')->nullable();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->json('response_headers')->nullable();
            $table->text('response_body')->nullable();
            $table->decimal('time', 8, 6);
            $table->json('extra')->nullable();

            $table->timestamps();
        });
    }

    public function down ()
    {
        Schema::dropIfExists('payment_debugs');
    }
};