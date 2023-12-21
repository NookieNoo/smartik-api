<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up () {
		Schema::create('user_settings', function (Blueprint $table) {
			$table->id();
			$table->morphs('user');
			$table->string('key');
			$table->json('value')->nullable();

			$table->timestamps();
		});
	}

	public function down () {
		Schema::dropIfExists('user_settings');
	}
};