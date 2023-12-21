<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up () {
		Schema::create('product_energies', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('product_id')->index();
			$table->decimal('calories', 5, 1)->nullable();
			$table->decimal('protein', 4, 1)->nullable();
			$table->decimal('fat', 4, 1)->nullable();
			$table->decimal('carbon', 4, 1)->nullable();
		});
	}

	public function down () {
		Schema::dropIfExists('product_energies');
	}
};