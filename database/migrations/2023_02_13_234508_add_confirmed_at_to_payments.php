<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('extra');
        });
    }

    public function down ()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('confirmed_at');
        });
    }
};