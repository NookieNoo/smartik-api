<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('type');
            $table->unsignedBigInteger('provider_id');
            $table->json('data')->nullable();
            $table->json('extra')->nullable();

            $table->timestamps();

            $table->index(['date', 'type', 'provider_id']);
        });
    }

    public function down ()
    {
        Schema::dropIfExists('integrations');
    }
};