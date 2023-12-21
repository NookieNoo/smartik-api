<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::table('kkm_checks', function (Blueprint $table) {
            $table->dropIndex('kkm_checks_uuid_unique');
            $table->unique(['uuid', 'type']);
        });
    }

    public function down ()
    {
        Schema::table('kkm_checks', function (Blueprint $table) {
            $table->dropIndex('kkm_checks_uuid_type_unique');
            $table->unique('uuid');
        });
    }
};