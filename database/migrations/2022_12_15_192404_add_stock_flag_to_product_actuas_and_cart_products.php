<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::table('product_actuals', function (Blueprint $table) {
            $table->boolean('from_stock')->default(false)->after('product_price_id');
        });
        Schema::table('cart_products', function (Blueprint $table) {
            $table->boolean('from_stock')->default(false)->after('product_price_id');
        });
    }

    public function down ()
    {
        Schema::table('product_actuals', function (Blueprint $table) {
            $table->dropColumn('from_stock');
        });
        Schema::table('cart_products', function (Blueprint $table) {
            $table->dropColumn('from_stock');
        });
    }
};