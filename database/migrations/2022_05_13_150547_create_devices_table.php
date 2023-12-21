<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up () {
		Schema::create('devices', function (Blueprint $table) {
			$table->id();
			$table->morphs("user");
			$table->uuid()->unique();
			$table->string("brand")->nullable();
			$table->string("manufacturer")->nullable();
			$table->string("model_name")->nullable();
			$table->string("os_name")->nullable();
			$table->string("os_version")->nullable();
			$table->string("device_name")->nullable();
			$table->string("app_version")->nullable();
			//
			$table->timestamps();
		});
	}

	public function down () {
		Schema::dropIfExists('devices');
	}
};