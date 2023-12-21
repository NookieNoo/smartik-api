<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up () {
		Schema::create('promos', function (Blueprint $table) {
			$table->id();
			$table->string('name')->nullable();
			$table->string('code')->unique();
			$table->enum('type', ['value', 'percent', 'fail'])->default('value');
			$table->unsignedSmallInteger('discount')->default(0);
			$table->unsignedInteger('count')->default(0);
			$table->boolean('reusable')->default(false);
			$table->unsignedTinyInteger('reusable_limit')->default(1);
			$table->unsignedSmallInteger('from_sum')->default(0);

			$table->timestamp('started_at')->nullable();
			$table->timestamp('ended_at')->nullable();
			$table->timestamps();
		});
	}

	public function down () {
		Schema::dropIfExists('promos');
	}
};