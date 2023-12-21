<?php

namespace App\Models;

use App\Enums\CartStatus;
use App\Notifications\Notifiable;
use App\Traits\HasDevice;
use App\Traits\LastActive;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, LastActive, HasDevice, InteractsWithMedia, SoftDeletes;

    protected $casts = [
        'birthday_at' => 'date'
    ];

    public function providers (): HasMany
    {
        return $this->hasMany(UserProvider::class);
    }

    public function settings (): MorphMany
    {
        return $this->morphMany(UserSettings::class, 'user');
    }

    public function phone (): HasOne
    {
        return $this->hasOne(UserProvider::class)->where('type', 'phone')->latestOfMany();
    }

    /*public function email (): HasOne
    {
        return $this->hasOne(UserProvider::class)->where('type', 'email')->latestOfMany();
    }*/

    public function carts (): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function cart (): HasOne
    {
        return $this->hasOne(Cart::class)->whereIn('status', [CartStatus::ACTIVE])->latestOfMany();
    }

    public function push_tokens (): HasMany
    {
        return $this->hasMany(UserPushToken::class);
    }

    public function push_token (): HasOne
    {
        return $this->hasOne(UserPushToken::class)->latestOfMany();
    }

    public function favorites (): MorphMany
    {
        return $this->morphMany(Favorite::class, 'user');
    }

    public function addresses (): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function payments (): HasMany
    {
        return $this->hasMany(UserPayment::class);
    }

    public function orders (): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function lastOrders (): HasMany
    {
        return $this->hasMany(Order::class)->orderBy('id', 'DESC')->limit(20);
    }

    public function promos(): HasMany
    {
        return $this->hasMany(UserPromo::class, 'user_id', 'id');
    }

    protected function invitedUsers(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                $ids = self::select('user_promos.from_user_id')
                    ->leftJoin('user_promos', 'user_promos.user_id', 'users.id')
                    ->where('user_promos.user_id', $this->id)
                    ->get()->pluck('from_user_id');

                return self::find($ids);
            },
        );
    }

    public function registerMediaConversions (Media $media = null): void
    {
        $this
            ->addMediaConversion('thumb')
            ->fit(Manipulations::FIT_CROP, 128, 128)
            ->nonQueued();

        $this
            ->addMediaConversion('big')
            ->fit(Manipulations::FIT_CROP, 600, 600)
            ->nonQueued();
    }

    public function registerMediaCollections (): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public static function findByPhone (string $phone): self
    {
        return self::whereHas('providers', function ($query) use ($phone) {
            $query->where('type', 'phone')
                ->where('value', $phone);
        })->first();
    }

    public static function findByUuid (string $uuid): self
    {
        return self::where('uuid', $uuid)->first();
    }

    protected function systemName (): Attribute
    {
        // Если system_name не задан, то рандомизируем его

        return Attribute::make(
            get: function ($value) {
                if (!$value) {
                    $rand = rand(1000000, 9999999);
                    $set = false;
                    while (!$set) {
                        $test = User::where('system_name', $rand)->first();
                        if (!$test) {
                            $this->system_name = $rand;
                            if ($this->exists) {
                                $this->save();
                            }
                            $value = $rand;
                            $set = true;
                        }
                    }
                }
                return $value;
            },
        );
    }

    public function afCallbackData(): HasOne
    {
        return $this->hasOne(AfCallbackData::class)->latestOfMany();
    }

    public function afCallbackDataTemp(): HasOne
    {
        return $this->hasOne(AfCallbackDataTemp::class)->latestOfMany();
    }

    public function afData()
    {
        $afData = null;
        if (!empty($this->afCallbackData?->data)) {
            $afData = $this->afCallbackData?->data;
        } else {
            $afData = $this->afCallbackDataTemp?->data;
        }
        return $afData;
    }

    protected function installType(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                return $this->afData()['data']['af_status'] ?? 'Нет информации';
            },
        );
    }

    protected function mediaSource(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                return $this->afData()['data']['media_source'] ?? 'Не указан';
            },
        );
    }

    protected function campaign(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                return $this->afData()['data']['campaign'] ?? 'Не указан';
            },
        );
    }

    protected function campaignId(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                return $this->afData()['data']['campaign_id'] ?? 'Не указан';
            },
        );
    }
    protected function agency(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                return $this->afData()['data']['agency'] ?? 'Не указан';
            },
        );
    }

    protected function afCpi(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->afData()['data']['af_cpi'] ?? 'Не указан'
        );
    }

    protected function installTime(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->afData()['data']['install_time'] ?? 'Не указан'
        );
    }

    protected function clickTime(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->afData()['data']['click_time'] ?? 'Не указан'
        );
    }


    public static function checkPersonalPromo (?string $code): array|false
    {
        if ($code === null) return false;
        $code = Str::upper($code);
        if (str_starts_with($code, 'DLR') || str_starts_with($code, 'MNY')) {
            $type = substr($code, 0, 3);
            $userSystemName = substr($code, 3);
            $user = User::where('system_name', $userSystemName)->first();
            if ($user) {
                $promo_settings = match ($type) {
                    "DLR" => [
                        "type" => "delivery",
                    ],
                    "MNY" => [
                        "code"     => $code,
                        "type"     => "value",
                        "discount" => 300,
                        "from_sum" => 1000
                    ]
                };
                return [
                    "user_id" => $user->id,
                    "promo"   => [
                        ...$promo_settings,
                        "name"     => "Бонусный гостинец от " . ($user->name ?? "друга"),
                        "code"     => $code,
                        "reusable" => false,
                        "active"   => true
                    ]
                ];
            }
        }
        return false;
    }

    public function routeNotificationForFcm ()
    {
        return $this->push_tokens()->where('token_type', 'fcm')->orderBy('id', 'desc')->first()?->token;
    }
}
