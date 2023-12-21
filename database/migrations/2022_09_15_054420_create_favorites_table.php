<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up () {
		Schema::create('favorites', function (Blueprint $table) {
			$table->id();
			$table->morphs('model');
			$table->morphs('user');
			$table->timestamp('created_at')->useCurrent();
			$table->softDeletes();
		});
	}

	public function down () {
		Schema::dropIfExists('favorites');
	}
};