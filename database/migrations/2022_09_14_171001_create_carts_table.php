<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up () {
		Schema::create('carts', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('user_id')->index();
			$table->enum('status', [
				'active',
				'canceled:user',
				'canceled:time',
				'canceled:replace',
				'done'
			])->default('active')->index();

			$table->timestamps();
			$table->softDeletes();
		});
	}

	public function down () {
		Schema::dropIfExists('carts');
	}
};