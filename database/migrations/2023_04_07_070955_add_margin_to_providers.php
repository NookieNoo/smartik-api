<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up (): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->unsignedMediumInteger('margin')->default(70)->after('type');
        });
    }

    public function down (): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn('margin');
        });
    }
};
