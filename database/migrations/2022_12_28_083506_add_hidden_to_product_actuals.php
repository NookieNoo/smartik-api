<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::table('product_actuals', function (Blueprint $table) {
            $table->boolean('hidden')->after('discount_percent');
        });
    }

    public function down ()
    {
        Schema::table('product_actuals', function (Blueprint $table) {
            $table->dropColumn('hidden');
        });
    }
};