<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->unsignedBigInteger('order_id')->index();
            $table->enum('payment_system', ['lifepay'])->default('lifepay');
            $table->enum('payment_method', ['creditcard'])->default('creditcard');
            $table->decimal('sum');
            $table->enum('status', [
                'start',
                'hold',
                'refund',
                'done',
                'error',
                'canceled:user',
                'canceled:admin',
                'canceled:time'
            ])->default('start')->index();
            $table->json('extra')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down ()
    {
        Schema::dropIfExists('payments');
    }
};