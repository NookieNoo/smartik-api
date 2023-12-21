<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::table('cart_products', function (Blueprint $table) {
            $table->dropColumn('isDeleted');
            $table->enum('status', [
                'canceled:actuals',
                'canceled:provider',
                'canceled:sdg',
                'canceled:user',
                'changed:provider',
                'changed:sdg',
                'changed:user',
                'confirm',
                'done'
            ])->nullable()->after('price');
        });
    }

    public function down ()
    {
        Schema::table('cart_products', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->boolean('isDeleted')->after('price')->default(false);
        });
    }
};