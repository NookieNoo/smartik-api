<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_eans', function (Blueprint $table) {
            DB::statement('ALTER TABLE product_eans MODIFY ean varchar(255) NOT NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_eans', function (Blueprint $table) {
            DB::statement('ALTER TABLE product_eans MODIFY ean bigint unsigned not null');
        });
    }
};
