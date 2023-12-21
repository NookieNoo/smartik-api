<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up () {
		Schema::create('product_prices', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();
			$table->unsignedBigInteger('provider_id')->index();
			$table->unsignedBigInteger('product_id')->index();
			$table->date('date')->index();
			$table->decimal('count')->default(0);
			$table->decimal('price')->default(0);
			$table->decimal('start_price')->default(0);
			$table->decimal('finish_price')->default(0);

			$table->timestamp('manufactured_at')->nullable();
			$table->timestamp('expired_at')->nullable();
			$table->timestamp('created_at')->useCurrent();
			$table->timestamp('soldout_at')->nullable();
		});
	}

	public function down () {
		Schema::dropIfExists('product_prices');
	}
};