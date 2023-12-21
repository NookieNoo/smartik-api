<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up () {
		Schema::create('product_actuals', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('product_id')->index();
			$table->unsignedBigInteger('product_price_id')->index();
			$table->decimal('price')->default(0);
			$table->decimal('count')->default(0);
			$table->unsignedTinyInteger('days_left');
			$table->unsignedTinyInteger('days_left_percent');
			$table->unsignedMediumInteger('discount');
			$table->unsignedTinyInteger('discount_percent');

			$table->timestamps();
		});
	}

	public function down () {
		Schema::dropIfExists('product_actuals');
	}
};