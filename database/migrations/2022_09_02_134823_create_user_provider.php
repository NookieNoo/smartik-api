<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up () {
		Schema::create('user_providers', function (Blueprint $table) {
			$table->id();

			$table->unsignedBigInteger('user_id')->index();
			$table->string('type');
			$table->string('value')->unique();
			$table->json('extra')->nullable();

			$table->timestamps();
		});
	}

	public function down () {
		Schema::dropIfExists('user_providers');
	}
};