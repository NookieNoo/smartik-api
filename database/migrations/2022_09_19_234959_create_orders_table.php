<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('cart_id')->index();
            $table->unsignedBigInteger('user_address_id')->index();
            $table->unsignedBigInteger('user_payment_id')->index();

            $table->enum('status', [
                'created',
                'payment:process',
                'payment:done',
                'payment:problem',
                'delivery:created',
                'delivery:performed',
                'delivery:on_way',
                'delivery:arrived',
                'done',
                'canceled:user',
                'canceled:driver',
                'canceled:manager'
            ])->default('created')->index();

            $table->decimal('sum_products')->default(0);
            $table->decimal('delivery_price')->default(0);
            $table->decimal('promo_discount')->default(0);
            $table->decimal('sum_final')->default(0);

            $table->timestamp('delivery_at')->nullable();
            $table->timestamp('delivery_change_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down ()
    {
        Schema::dropIfExists('orders');
    }
};