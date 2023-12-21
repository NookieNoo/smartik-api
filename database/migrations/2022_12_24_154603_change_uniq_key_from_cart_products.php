<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::table('cart_products', function (Blueprint $table) {
            $table->dropIndex('cart_products_cart_id_product_price_id_unique');
            $table->unique(['cart_id', 'product_price_id', 'status']);
        });
    }

    public function down ()
    {
        Schema::table('cart_products', function (Blueprint $table) {
            $table->dropIndex('cart_products_cart_id_product_price_id_status_unique');
            $table->unique(['cart_id', 'product_price_id']);
        });
    }
};