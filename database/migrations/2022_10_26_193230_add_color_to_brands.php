<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->string('color', 7)->after('slug')->default('#ffffff')->nullable();
            $table->unsignedMediumInteger('position')->after('color')->default(0);
        });
    }

    public function down ()
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('color');
            $table->dropColumn('position');
        });
    }
};