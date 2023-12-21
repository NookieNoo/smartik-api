<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up () {
		Schema::table('product_prices', function (Blueprint $table) {
			$table->decimal('price')->comment('цена для клиентов')->change();
			$table->decimal('start_price')->comment('цена розницы')->change();
			$table->decimal('finish_price')->comment('цена наша')->change();
		});
	}

	public function down () {
		Schema::table('product_prices', function (Blueprint $table) {

		});
	}
};