<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Provider extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    public function registerMediaConversions (Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Manipulations::FIT_FILL, 128, 128)
            ->nonQueued();

        $this->addMediaConversion('big')
            ->fit(Manipulations::FIT_FILL, 512, 512)
            ->keepOriginalImageFormat()
            ->nonQueued();
    }

    public function registerMediaCollections (): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }

    protected function integrationEmails (): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!$value) {
                    return [];
                }
                return json_decode($value, true);
            },
        );
    }
}