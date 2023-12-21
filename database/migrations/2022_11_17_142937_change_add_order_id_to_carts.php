<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->after('status')->default(0);
        });
    }

    public function down ()
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('order_id');
        });
    }
};