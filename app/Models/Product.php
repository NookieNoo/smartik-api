<?php

namespace App\Models;

use App\Enums\ProductWeightType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia, Searchable;

    protected $casts = [
        'weight_type' => ProductWeightType::class,
        'price'       => 'float',
        'vat'         => 'int',
        'exire_days'  => 'int'
    ];

    public function brand (): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function eans (): HasMany
    {
        return $this->hasMany(ProductEan::class);
    }

    public function energy (): HasOne
    {
        return $this->hasOne(ProductEnergy::class);
    }

    public function catalogs (): BelongsToMany
    {
        return $this->belongsToMany(Catalog::class, 'product_catalog');
    }

    public function prices (): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function actuals (): HasMany
    {
        return $this->hasMany(ProductActual::class);
    }

    public function banner(): MorphMany
    {
        return $this->morphMany(Banner::class, 'bannerable');
    }

    public function registerMediaConversions (Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(128)
            ->height(128)
            ->keepOriginalImageFormat()
            ->nonQueued();

        $this->addMediaConversion('big')
            ->width(512)
            ->height(512)
            ->keepOriginalImageFormat()
            ->nonQueued();

        $this->addMediaConversion('full')
            ->width(1200)
            ->height(1500)
            ->keepOriginalImageFormat()
            ->nonQueued();
    }

    public function registerMediaCollections (): void
    {
        $this->addMediaCollection('images')->singleFile();
        $this->addMediaCollection('possible');
    }

    public function toSearchableArray ()
    {
        return [
            'id'        => $this->id,
            'name' => $this->name,
            //'brand'       => $this->brand->name
        ];
    }

    public static function findByUuid (string $uuid): ?self
    {
        return self::where('uuid', $uuid)->first();
    }

    public static function findByEan (string $ean): ?self
    {
        return self::whereHas('eans', function ($query) use ($ean) {
            $query->where('ean', $ean)->limit(1);
        })->first();
    }

    protected function weightKg (): Attribute
    {
        return Attribute::make(
            get: function () {
                switch ($this->weight_type) {
                    case ProductWeightType::G:
                    case ProductWeightType::ML:
                    {
                        return $this->weight / 1000;
                    }
                    default:
                    {
                        return $this->weight;
                    }
                }
            },
        );
    }
}
