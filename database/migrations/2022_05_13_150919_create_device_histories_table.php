<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up () {
		Schema::create('device_histories', function (Blueprint $table) {
			$table->morphs("user");
			$table->json("device")->nullable();
			$table->timestamp("created_at");
		});
	}

	public function down () {
		Schema::dropIfExists('device_histories');
	}
};