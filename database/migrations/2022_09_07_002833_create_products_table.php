<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up () {
		Schema::create('products', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();
			$table->unsignedBigInteger('brand_id')->index();
			$table->string('name')->nullable();
			$table->text('description')->nullable();
			$table->text('compound')->nullable();
			$table->double('weight', 9, 3)->default(0);
			$table->enum('weight_type', ["count", "ml", "kg"])->default('count');

			$table->timestamps();
			$table->softDeletes();
		});
	}

	public function down () {
		Schema::dropIfExists('products');
	}
};