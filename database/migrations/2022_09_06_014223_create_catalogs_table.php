<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Kalnoy\Nestedset\NestedSet;

return new class extends Migration {
	public function up () {
		Schema::create('catalogs', function (Blueprint $table) {
			$table->id();
			$table->uuid()->unique();
			$table->unsignedInteger('left')->default(0);
			$table->unsignedInteger('right')->default(0);
			$table->unsignedInteger('parent_id')->nullable();
			$table->string('name');
			$table->string('slug')->index();

			$table->timestamps();

			$table->index(['left', 'right', 'parent_id']);
		});
	}

	public function down () {
		Schema::dropIfExists('catalogs');
	}
};