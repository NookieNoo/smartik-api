<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up (): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->json('prepayment_cast')->nullable()->after('order_id');
            $table->json('final_cast')->nullable()->after('prepayment_cast');
        });
    }

    public function down (): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn(['prepayment_cast', 'final_cast']);
        });
    }
};