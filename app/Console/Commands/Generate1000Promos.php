<?php

namespace App\Console\Commands;

use App\Models\Promo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Generate1000Promos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promos:generate-like {--id=*} {--quantity=2} {--prefix=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Создать n промокодов, аналогичных переданному id промокода";

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // SM, PO, SC, SB, PP, BF
        $promoIds = $this->option('id');
        if (!$promoIds) {
            $this->error('Empty id options!');
            return Command::FAILURE;
        }

        $promoPrefix = $this->option('prefix');
        if (!$promoPrefix) {
            $this->error('Empty prefix options!');
            return Command::FAILURE;
        }

        $promoId = (int) $promoIds[0];
        $quantity = (int) $this->option('quantity');
        $this->line('Start: ' . $promoId);
        $this->line('Prefix: ' . $promoPrefix);

        $promoOriginal = Promo::find($promoId);
        if (!$promoOriginal) {
            $this->error('Promo not found');
            return Command::FAILURE;
        }

        $this->line($promoOriginal->name);
        $this->line($promoId);
        $this->line($promoOriginal->code);

        $promos = collect();

        try {
            DB::beginTransaction();

            while ($quantity) {
                $quantity--;

                $newPromo = $promoOriginal->replicate(['id', 'created_at', 'updated_at'])->fill([
                    'code' => mb_strtoupper($promoPrefix . dechex(11000 - $quantity))
                ]);

                $newPromo->save();
                $promoOriginal->tags->each(fn($tag) => $newPromo->attachTag($tag));
                $promos->add($newPromo);
            }

            $file = fopen(storage_path('app/public') . '/promos.csv', 'w');
            $promos->each(function ($promo) use ($file) {
                fputcsv($file, $promo->only(['name', 'code', 'discount']));
            });
            fclose($file);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error in transaction');
            throw $e;
        }

        $this->line('Файл с промокодами: ' . storage_path('app/public') . '/promos.csv');
        $this->line('success');
        return Command::SUCCESS;
    }
}
