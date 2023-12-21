<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up (): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->string('type')->default('value')->change();
        });
    }

    public function down (): void
    {
        DB::statement("ALTER TABLE promos MODIFY COLUMN type enum ('value', 'percent', 'fail') default 'value' NOT NULL");
    }
};