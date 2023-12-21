<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up () {
		Schema::create('cart_products', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('cart_id');
			$table->unsignedBigInteger('product_id')->index();
			$table->unsignedBigInteger('product_price_id');
			$table->decimal('count')->default(0);
			$table->decimal('price')->default(0);

			$table->timestamps();

			$table->unique(['cart_id', 'product_price_id']);
		});
	}

	public function down () {
		Schema::dropIfExists('cart_products');
	}
};