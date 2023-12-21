<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up ()
    {
        DB::statement("ALTER TABLE cart_products MODIFY COLUMN status           enum ('canceled:actual', 'canceled:provider', 'canceled:sdg', 'canceled:user', 'order', 'confirm', 'warehouse', 'delivery', 'done') null");
    }

    public function down ()
    {
        DB::statement("ALTER TABLE cart_products MODIFY COLUMN status           enum ('canceled:actual', 'canceled:provider', 'canceled:sdg', 'canceled:user', 'order', 'confirm', 'done') null");
    }
};