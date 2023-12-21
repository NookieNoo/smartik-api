<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Trin4ik\DevinoApi\Enums\DevinoNotificationStatus;

return new class extends Migration {
	public function up () {
		Schema::create('devino_notifications', function (Blueprint $table) {
			$table->id();
			$table->morphs('notifable');
			$table->string('devino_id');
			$table->string('sender')->nullable()->index();
			$table->char('to', 11)->nullable()->index();
			$table->text('message')->nullable();
			$table->enum('status', DevinoNotificationStatus::values())->default('new');
			$table->json('log')->nullable();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down () {
		Schema::dropIfExists('devino_notifications');
	}
};
