<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::table('kkm_checks', function (Blueprint $table) {
            $table->json('result')->nullable()->after('checks');
            $table->renameColumn('checks', 'check');
        });
    }

    public function down ()
    {
        Schema::table('kkm_checks', function (Blueprint $table) {
            $table->dropColumn('result');
            $table->renameColumn('check', 'checks');
        });
    }
};