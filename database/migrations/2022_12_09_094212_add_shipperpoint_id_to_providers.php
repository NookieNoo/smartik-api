<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->string('shipperpoint_id')->after('integration_emails')->nullable();
        });
    }

    public function down ()
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn('shipperpoint_id');
        });
    }
};