<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('marked')->default(false)->after('weight_type');
        });
    }

    public function down ()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('marked');
        });
    }
};