<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price')->after('weight_type');
            $table->smallInteger('expire_days')->after('price');
        });
    }

    public function down ()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['price', 'expire_days']);
        });
    }
};