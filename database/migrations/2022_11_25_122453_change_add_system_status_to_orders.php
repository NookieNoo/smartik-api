<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up ()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('system_status', [
                'in_app',
                'send_to_provider',
                'get_from_provider',
                'send_to_sdg_inbound',
                'get_from_sdg_arv',
                'send_to_sdg_outbound',
                'get_from_sdg_shp',
                'get_from_sdg_wbl',
                'get_from_ats_in_radius',
                'get_from_ats_on_point',
                'get_from_ats_done',
                'get_from_ats_cancel',
            ])->default('in_app')->index();
        });
    }

    public function down ()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('system_status');
        });
    }
};