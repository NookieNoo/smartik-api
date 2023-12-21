<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up ()
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN system_status enum ('in_app', 'send_to_provider', 'get_from_provider', 'send_to_sdg_inbound', 'get_from_sdg_arv', 'send_to_sdg_outbound', 'get_from_sdg_shp', 'get_from_sdg_wbl', 'get_from_ats_in_radius', 'get_from_ats_on_point', 'get_from_ats_done', 'get_from_ats_cancel') default 'in_app' NOT NULL AFTER status");
        DB::statement("ALTER TABLE orders MODIFY COLUMN status enum ('created', 'payment:process', 'payment:done', 'payment:problem', 'delivery:created', 'delivery:performed', 'delivery:on_way', 'delivery:arrived', 'done', 'canceled:user', 'canceled:driver', 'canceled:manager') default 'created' NOT NULL");
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('in_work');
        });
    }

    public function down ()
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN system_status enum ('in_app', 'send_to_provider', 'get_from_provider', 'send_to_sdg_inbound', 'get_from_sdg_arv', 'send_to_sdg_outbound', 'get_from_sdg_shp', 'get_from_sdg_wbl', 'get_from_ats_in_radius', 'get_from_ats_on_point', 'get_from_ats_done', 'get_from_ats_cancel') default 'in_app' NOT NULL AFTER deleted_at");
        DB::statement("ALTER TABLE orders MODIFY COLUMN status enum ('created', 'payment:process', 'payment:done', 'payment:problem', 'delivery:created', 'delivery:performed', 'delivery:on_way', 'delivery:arrived', 'done', 'canceled:user', 'canceled:driver', 'canceled:manager') default 'created' NULL");
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('in_work')->after('status')->default(false)->index();
        });
    }
};