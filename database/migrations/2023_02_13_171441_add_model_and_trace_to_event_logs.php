<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::table('event_logs', function (Blueprint $table) {
            $table->string('model_type')->after('event')->nullable();
            $table->unsignedBigInteger('model_id')->after('model_type');
            $table->json('trace')->after('extra');

            $table->index(['model_type', 'model_id']);
        });
    }

    public function down ()
    {
        Schema::table('event_logs', function (Blueprint $table) {
            $table->dropColumn(['model_type', 'model_id', 'trace']);
        });
    }
};