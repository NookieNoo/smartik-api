<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up () {
		Schema::create('cart_promo', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('cart_id');
			$table->unsignedBigInteger('promo_id')->index();

			$table->timestamp('created_at')->useCurrent();

			$table->unique(['cart_id', 'promo_id']);
		});
	}

	public function down () {
		Schema::dropIfExists('cart_promo');
	}
};