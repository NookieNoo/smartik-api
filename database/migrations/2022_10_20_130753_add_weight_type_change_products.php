<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        DB::statement('ALTER TABLE products MODIFY COLUMN weight_type enum("count", "ml", "l", "g", "kg") NOT NULL DEFAULT "count"');
    }

    public function down ()
    {
        DB::statement('ALTER TABLE products MODIFY COLUMN weight_type enum("count", "ml", "kg") NOT NULL DEFAULT "count"');
    }
};