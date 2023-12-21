<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->json('extra')->nullable()->after('floor');
        });
    }

    public function down ()
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->dropColumn('extra');
        });
    }
};