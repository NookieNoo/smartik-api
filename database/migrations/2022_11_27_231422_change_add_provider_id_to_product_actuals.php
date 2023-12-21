<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::table('product_actuals', function (Blueprint $table) {
            $table->unsignedBigInteger('provider_id')->index()->after('id');
        });
    }

    public function down ()
    {
        Schema::table('product_actuals', function (Blueprint $table) {
            $table->dropColumn('provider_id');
        });
    }
};