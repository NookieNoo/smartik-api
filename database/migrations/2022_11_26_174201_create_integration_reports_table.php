<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::create('integration_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id')->index();
            $table->unsignedBigInteger('mailbox_id')->index();
            $table->string('mailbox_type')->nullable();
            $table->string('file')->nullable();
            $table->json('report')->nullable();
            $table->json('extra')->nullable();
            $table->date('date')->index();

            $table->timestamps();
        });
    }

    public function down ()
    {
        Schema::dropIfExists('integration_reports');
    }
};