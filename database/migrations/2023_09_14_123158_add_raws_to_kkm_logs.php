<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up (): void
    {
        Schema::table('kkm_logs', function (Blueprint $table) {
            $table->text('request_raw')->after('request')->nullable();
            $table->text('response_raw')->after('response')->nullable();
            $table->decimal('response_time', 6, 4)->after('response_raw')->default(0);
        });
    }

    public function down (): void
    {
        Schema::table('kkm_logs', function (Blueprint $table) {
            $table->dropColumn(['request_raw', 'response_raw', 'response_time']);
        });
    }
};
