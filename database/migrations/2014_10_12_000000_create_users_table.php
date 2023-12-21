<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up () {
		Schema::create('users', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();
			$table->string('name')->nullable();
			$table->enum('sex', ['man', 'woman'])->nullable();

			$table->timestamp('birthday_at')->nullable();
			$table->timestamp('last_active_at')->nullable();
			$table->timestamps();
			$table->softDeletes();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down () {
		Schema::dropIfExists('users');
	}
};
