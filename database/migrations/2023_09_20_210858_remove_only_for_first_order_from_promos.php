<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up (): void
    {
        $first_tag = \Spatie\Tags\Tag::findOrCreateFromString('Приветствие', 'promo');
        \App\Models\Promo::where('only_for_first_order', 1)
            ->get()
            ->each(function (\App\Models\Promo $promo) use ($first_tag) {
                $promo->attachTag($first_tag);
            });

        Schema::table('promos', function (Blueprint $table) {
            $table->dropColumn('only_for_first_order');
        });
    }

    public function down (): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->boolean('only_for_first_order')->default(false)->after('active');
        });
    }
};
