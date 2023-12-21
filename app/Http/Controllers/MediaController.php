<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Catalog;
use App\Models\Product;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravolt\Avatar\Facade as Avatar;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaController
{
    public function avatar (string $uuid, $type = 'thumb')
    {
        $user = User::findByUuid($uuid);
        $media = $user->getFirstMedia('avatar');
        if (!$media) {
            return response(base64_decode(explode(',',
                Avatar::create(Str::upper($user->name))
                    ->setDimension(128)
                    ->setFontSize(60)
                    ->setShape('square')
                    ->toBase64()
                                          )[1]))
                ->header('Content-Type', 'image/png')
                ->header('Cache-Control', 'max-age=604800');
        }

        return $this->fileResponse($media, $type);
    }

    public function catalog (string $uuid, $type = 'thumb')
    {
        $catalog = Catalog::where('uuid', $uuid)->first();
        $media = $catalog->getFirstMedia('icon');
        if (!$media) {
            return response(base64_decode(explode(',',
                Avatar::create(Str::upper($catalog->name))
                    ->setDimension(128)
                    ->setFontSize(60)
                    ->setShape('square')
                    ->toBase64()
                                          )[1]))
                ->header('Content-Type', 'image/png')
                ->header('Cache-Control', 'max-age=604800');
        }

        return $this->fileResponse($media, $type);
    }

    public function brand (string $slug, $type = 'thumb')
    {
        $brand = Brand::where('slug', $slug)->withTrashed()->first();
        $media = $brand->getFirstMedia('logo');
        if (!$media) {
            return response(base64_decode(explode(',',
                Avatar::create(Str::upper($brand->name))
                    ->setDimension(128)
                    ->setFontSize(60)
                    ->setShape('square')
                    ->toBase64()
                                          )[1]))
                ->header('Content-Type', 'image/png')
                ->header('Cache-Control', 'max-age=604800');
        }

        return $this->fileResponse($media, $type);
    }

    public function product (string $uuid, $type = 'thumb')
    {
        $product = Product::where('uuid', $uuid)->withTrashed()->first();
        $media = $product->getMedia('images')->first();
        //$media = $product->getMedia('images')->where('uuid', $media_uuid)->first();
        if (!$media) {
            return response(base64_decode(explode(',',
                Avatar::create(Str::upper($product->name))
                    ->setDimension(128)
                    ->setFontSize(60)
                    ->setShape('square')
                    ->toBase64()
                                          )[1]))
                ->header('Content-Type', 'image/png')
                ->header('Cache-Control', 'max-age=604800');
        }

        return $this->fileResponse($media, $type);
    }

    public function banner (string $uuid, $type = 'thumb')
    {
        $banner = Banner::where('uuid', $uuid)->first();
        $media = $banner->getMedia('banner')->first();
        if (!$banner) {
            return response(base64_decode(explode(',',
                Avatar::create(Str::upper($banner->name))
                    ->setDimension(128)
                    ->setFontSize(60)
                    ->setShape('square')
                    ->toBase64()
            )[1]))
                ->header('Content-Type', 'image/png')
                ->header('Cache-Control', 'max-age=604800');
        }

        return $this->fileResponse($media, $type);
    }


    function fileResponse (Media $media, ?string $type)
    {
        if ($media->mime_type === 'image/svg') {
            $type = '';
        }
        return response(
            $type ? Storage::get($media->getPath($type)) : Storage::get($media->getPath())
        )
            ->header('Content-Type', $media->mime_type)
            ->header('Cache-Control', 'max-age=604800');

    }
}
