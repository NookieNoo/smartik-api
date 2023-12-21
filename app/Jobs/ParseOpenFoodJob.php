<?php

namespace App\Jobs;

use App\Enums\ProductWeightType;
use App\Models\Brand;
use App\Models\Catalog;
use App\Models\Product;
use App\Models\ProductEan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use OpenFoodFacts\Laravel\Facades\OpenFoodFacts;

class ParseOpenFoodJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public function __construct () {
	}

	public function handle () {

		/*
		$products = Product::all();
		$products->each(function ($product) {
		  $product->eans()->delete();
		  $product->energy()->delete();
		  $product->clearMediaCollection('images');
		  $product->forceDelete();
		});
		*/

		Catalog::withDepth()->orderBy('depth', 'desc')->each(function ($catalog) {
			OpenFoodFacts::find($catalog->name)
				->filter(fn($item) => !empty($item['product_name_ru']))
				->each(function ($off) use ($catalog) {
					if (ProductEan::find($off['code'])) return;

					//dump($off);

					$brand = Brand::where('name', $off['brands'] ?? "Россия")->first();
					if (!$brand) {
						$brand = Brand::create([
							'name' => $off['brands'] ?? 'Россия',
							'slug' => Str::slug($off['brands'] ?? 'russia')
						]);
					}

					$weight = $off['product_quantity'] ?? 1;
					$weight_type = ProductWeightType::COUNT;

					if (isset($off['quantity'])) {
						if (preg_match('/(ml|kg|g|l|г|мл|л|кг)$/', $off['quantity'], $finish)) {
							switch ($finish[1] ?? false) {
								case 'ml':
								case 'мл':
								{
									$weight_type = ProductWeightType::ML;
									if ($weight > 10000) {
										$weight = $weight / 1000;
									}
									break;
								}
								case 'l':
								case 'л':
								{
									$weight_type = ProductWeightType::ML;
									if ($weight < 10) {
										$weight = $weight * 1000;
									}
									break;
								}
								case 'kg':
								case 'кг':
								{
									$weight_type = ProductWeightType::KG;
									if ($weight > 10) {
										$weight = $weight / 1000;
									}
									break;
								}
								case 'g':
								case 'г':
								{
									$weight_type = ProductWeightType::KG;
									if ($weight < 10000) {
										$weight = $weight * 1000;
									}
									break;
								}
							}
						}
					}

					$product = Product::create([
						'brand_id'    => $brand->id,
						'name'        => $off['product_name_ru'],
						'description' => null,
						'compound'    => !empty($off['ingredients_text_ru']) ? $off['ingredients_text_ru'] : null,
						'weight'      => $weight,
						'weight_type' => $weight_type
					]);

					$product->eans()->create([
						'ean' => $off['code']
					]);

					if (count($off['nutriments'] ?? []) && (isset($off['nutriments']['carbohydrates']) || isset($off['nutriments']['carbohydrates_100g'])) && (isset($off['nutriments']['proteins']) || isset($off['nutriments']['proteins_100g'])) && (isset($off['nutriments']['fat']) || isset($off['nutriments']['fat_100g']))) {
						$product->energy()->create([
							'calories' => $off['nutriments']['energy-kcal_100g'] ?? $off['nutriments']['energy-kcal'] ?? $off['nutriments']['energy-kj_100g'] * 0.239 ?? $off['nutriments']['energy-kj'] * 0.239,
							'protein'  => $off['nutriments']['proteins_100g'] ?? $off['nutriments']['proteins'],
							'fat'      => $off['nutriments']['fat_100g'] ?? $off['nutriments']['fat'],
							'carbon'   => $off['nutriments']['carbohydrates_100g'] ?? $off['nutriments']['carbohydrates'],
						]);
					}

					if (!empty($off['image_front_url'])) {
						$product->addMediaFromUrl($off['image_front_url'], ['image/*'])->toMediaCollection('images');
					}

					$catalog->products()->attach($product);
				});
		});
	}
}