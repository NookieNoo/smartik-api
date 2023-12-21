<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::table('provider_products', function (Blueprint $table) {
            $table->json('extra')->nullable()->after('product_id');
        });
    }

    public function down ()
    {
        Schema::table('provider_products', function (Blueprint $table) {
            $table->dropColumn('extra');
        });
    }
};