<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up () {
		Schema::create('user_addresses', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();
			$table->boolean('default')->default(false);
			$table->unsignedBigInteger('user_id')->index();
			$table->string('name')->nullable();
			$table->string('address')->nullable();
			$table->string('address_full')->nullable();
			$table->point('address_location')->nullable();
			$table->string('flat')->nullable();
			$table->string('entrance')->nullable();
			$table->string('floor')->nullable();

			$table->timestamps();
			$table->softDeletes();
		});
	}

	public function down () {
		Schema::dropIfExists('user_addresses');
	}
};