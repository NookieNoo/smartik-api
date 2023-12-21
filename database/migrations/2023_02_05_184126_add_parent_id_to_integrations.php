<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->after('id')->index();
        });
    }

    public function down ()
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->dropColumn('parent_id');
        });
    }
};