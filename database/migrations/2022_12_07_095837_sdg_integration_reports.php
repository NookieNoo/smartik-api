<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::create('sdg_integration_reports', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->index();
            $table->enum('side', ['in', 'out'])->default('in');
            $table->string('type')->nullable();
            $table->string('file')->nullable();
            $table->text('content')->nullable();

            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down ()
    {
        Schema::dropIfExists('sdg_integration_reports');
    }
};