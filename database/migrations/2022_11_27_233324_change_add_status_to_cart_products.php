<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::table('cart_products', function (Blueprint $table) {
            $table->boolean('isDeleted')->after('price')->default(false);
        });
    }

    public function down ()
    {
        Schema::table('cart_products', function (Blueprint $table) {
            $table->dropColumn('isDeleted');
        });
    }
};