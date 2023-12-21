<?php

namespace App\Http\Controllers\User;

use App\Events\User\UserAddPhoneEvent;
use App\Events\User\UserLoginBySmsEvent;
use App\Events\User\UserLogoutEvent;
use App\Events\User\UserLogoutFromAllEvent;
use App\Events\User\UserRemoveAddressEvent;
use App\Events\User\UserRemoveAvatarEvent;
use App\Events\User\UserRequestSmsEvent;
use App\Events\User\UserSignupEvent;
use App\Events\User\UserUpdateProfileEvent;
use App\Events\User\UserUpdateSettingsEvent;
use App\Events\User\UserUploadAvatarEvent;
use App\Events\User\UserUpsertAddressEvent;
use App\Events\User\UserUpsertPaymentEvent;
use App\Exceptions\Custom\PhoneNotFoundException;
use App\Exceptions\Custom\SmsActivationTimeoutException;
use App\Exceptions\Custom\UserUuidExistExtension;
use App\Exceptions\Custom\WrongSmsCodeException;
use App\Http\Controllers\ApiController;
use App\Http\Requests\User\UserActivateSmsRequest;
use App\Http\Requests\User\UserCreatePushToken;
use App\Http\Requests\User\UserPromoRequest;
use App\Http\Requests\User\UserRemovePushToken;
use App\Http\Requests\User\UserSamplePushRequest;
use App\Http\Requests\User\UserSignupRequest;
use App\Http\Requests\User\UserRequestSmsRequest;
use App\Http\Requests\User\UserUpdateProfileRequest;
use App\Http\Requests\User\UserUpdateSettingsRequest;
use App\Http\Requests\User\UserUploadAvatarRequest;
use App\Http\Requests\User\UserUpsertAddressRequest;
use App\Http\Requests\User\UserUpsertPaymentRequest;
use App\Http\Resources\User\PromoResource;
use App\Http\Resources\User\UserPromoResource;
use App\Http\Resources\User\UserResource;
use App\Models\AfCallbackData;
use App\Models\AfCallbackDataTemp;
use App\Models\AfEvent;
use App\Models\CartProduct;
use App\Models\Order;
use App\Models\Promo;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserPayment;
use App\Models\UserPromo;
use App\Models\UserProvider;
use App\Models\UserPushToken;
use App\Models\UserSettings;
use App\Notifications\Push\CartProductChangeNotification;
use App\Notifications\Push\OrderCancelNotification;
use App\Notifications\Push\OrderDeliveryOnWayNotification;
use App\Notifications\Push\OrderDoneNotification;
use App\Services\ActiveApi\Attributes\Position;
use App\Services\ActiveApi\Attributes\Title;
use App\Services\ActiveApi\Attributes\Variable;
use App\Services\Payment\PaymentInterface;
use App\Services\ShowcaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MoveMoveIo\DaData\Facades\DaDataAddress;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Trin4ik\DevinoApi\Facades\DevinoApi as SMS;

#[
    Title('Профиль', 'profile'),
    Position(2)
]
class ProfileController extends ApiController
{
    protected array $relations = [
        'cart' => [
            'products' => [
                'product',
                'product_price' => [
                    'actual'
                ]
            ]
        ],
        'addresses',
        'lastOrders',
        'favorites',
    ];

    #[
        Title('Вход', 'signup'),
        Position(1),
        Variable('token', type: 'string', description: 'Токен', response: 'response.data.token')
    ]
    public function signup (UserSignupRequest $request)
    {
        $user = User::whereUuid($request->input('uuid'))->first();
        if ($user) {
            if ($this->isAdmin) {
                $user->load($this->relations);
                $token = $user->createToken($user->device?->uuid ?? 'api');
                return $this->send((new UserResource($user))->additional([
                    'token' => explode('|', $token->plainTextToken)[1]
                ]));
            }
            throw new UserUuidExistExtension;
        }
        $user = User::create([
            'uuid' => $request->input('uuid')
        ]);

        event(new UserSignupEvent(user: $user));

        $user->load($this->relations);

        $token = $user->createToken($user->device?->uuid ?? 'api');
        return $this->send((new UserResource($user))->additional([
            'token' => explode('|', $token->plainTextToken)[1]
        ]));
    }

    #[
        Title('Выход', 'logout'),
        Position(4)
    ]
    public function logout ()
    {
        $this->user->currentAccessToken()->delete();
        event(new UserLogoutEvent);
        return $this->send(true);
    }

    #[
        Title('Выход из всех устройств', 'logout_all'),
        Position(4)
    ]
    public function logout_all ()
    {
        $this->user->tokens()->delete();
        event(new UserLogoutFromAllEvent);
        return $this->send(true);
    }

    #[
        Title('Запрос СМС', 'sms_request'),
        Position(2)
    ]
    public function sms_request (UserRequestSmsRequest $request)
    {
        $code = rand(1000, 9999);

        // ищем телефон, или создаём новый и привязываем к данному пользователю
        $phone = UserProvider::where('type', 'phone')->where('value', $request->input('phone'))->first();
        if (!$phone) {
            $phone = $this->user->providers()->create([
                'type'  => 'phone',
                'value' => $request->input('phone')
            ]);
            event(new UserAddPhoneEvent(data: $request->input('phone')));
        }

        // создаём новое sms подтвержение
        $phone->extra->code = $code;
        $phone->extra->code_at = Carbon::now()->format('Y-m-d H:i:s');

        $phone->save();

        //$user->notify(new SmsCodeSend(['code' => $code]));
        event(new UserRequestSmsEvent(data: $request->input('phone')));

        if (app()->environment('production')) {
            SMS::send([
                'to'   => $request->input('phone'),
                'text' => $code . ' код подтверждения номера телефона в сервисе Покупкин'
            ]);
        }

        return $this->send($this->isAdmin ? $code : true);
    }

    #[
        Title('Активация СМС', 'sms_activate'),
        Position(3),
        Variable('token', type: 'string', description: 'Токен', response: 'response.data.token')
    ]
    public function sms_activate (UserActivateSmsRequest $request, ShowcaseService $showcase)
    {
        $phone = UserProvider::where('type', 'phone')->where('value', $request->input('phone'))->first();

        // если телефона нет -- нафиг
        if (!$phone) {
            throw new PhoneNotFoundException;
        }

        // если смс код не совпал или ты не админ или не тестовый акк -- нафиг
        if (!(
            (string)$phone->extra->code === (string)$request->input('code') ||
            (
                $this->isAdmin &&
                (string)$request->input('code') === date('dm')
            ) ||
            (
                $request->input('phone') === '+79999999999' &&
                (int)$request->input('code') === 9999
            )
        )) {
            throw new WrongSmsCodeException;
        }

        // если ты не админ и от запроса кода прошло больше 10 минут -- нафиг
        /*if (!$this->isAdmin && $request->input('phone') !== '+79999999999') {
            if (
                Carbon::parse($phone->extra->code_at ?? Carbon::now())->add(10, 'minutes')->lessThan(Carbon::now()) ||
                Carbon::parse($phone->extra->code_at)->lessThan($phone->extra->verified_at)
            ) {
                throw new SmsActivationTimeoutException;
            }
        }*/

        event(new UserLoginBySmsEvent(data: ['user' => $phone->user, 'isAdmin' => $this->isAdmin]));

        $phone->extra->verified_at = Carbon::now()->format('Y-m-d H:i:s');
        $phone->save();

        // если текущая сессия отличается от найденной по телефону -- авторизуемся под найденным по телефону
        if ($phone->user->id !== $this->user->id && $request->input('phone') !== '+79999999999') {

            // но сначала копируем корзину, если она есть из текущего в найденного
            if ($this->user->cart) {
                $showcase->cart($this->user->cart)->move($phone->user);
            }

            // переносим пуш токен в пользователя по телефону
            $push = $this->user->routeNotificationForFcm();
            if ($push && !$phone->user->push_tokens()->where('token', $push)->count()) {
                $phone->user->push_tokens()->create([
                    'token_type' => 'fcm',
                    'token'      => $push,
                ]);
            }
            // и для пущего удаляем текущего пользователя, нефиг
            $this->user->push_tokens()->delete();
            $this->user->delete();

            $phone->user->tokens()->where('name', $user->device?->uuid ?? 'api')->delete();
            $token = $phone->user->createToken($user->device?->uuid ?? 'api');

            $phone->user->load($this->relations);

            return $this->send((new UserResource($phone->user))->additional([
                'token' => explode('|', $token->plainTextToken)[1]
            ]));
        }

        $phone->user->load($this->relations);

        $token = $phone->user->createToken($phone->user->device?->uuid ?? 'api');
        return $this->send((new UserResource($phone->user))->additional([
            'token' => explode('|', $token->plainTextToken)[1]
        ]));
    }

    #[
        Title('Информация', 'info'),
    ]
    public function info ()
    {
        $this->user->load($this->relations);
        return $this->send(new UserResource($this->user));
    }

    #[
        Title('Редактирование', 'update'),
    ]
    public function update (UserUpdateProfileRequest $request)
    {
        $this->user->update($request->safe()->all());
        event(new UserUpdateProfileEvent);
        $this->user->load($this->relations);
        return $this->send(new UserResource($this->user));
    }

    #[
        Title('Настройки', 'settings'),
    ]
    public function settings (UserUpdateSettingsRequest $request)
    {
        foreach ($request->input('settings') as $key => $settings) {
            if (in_array($key, ['notifications'])) {

                $tmp = $this->user->settings()->where('key', $key)->first();
                if (!$tmp) {
                    $tmp = new UserSettings([
                        'user_type' => get_class($this->user),
                        'user_id'   => $this->user->id,
                        'key'       => $key
                    ]);
                    $tmp->save();
                }
                $tmp->value = array_merge($tmp->value ?? [], $settings);
                event(new UserUpdateSettingsEvent(data: $tmp->value));
                $tmp->save();
            }
        }
        $this->user->load($this->relations);
        return $this->send(new UserResource($this->user));
    }

    #[
        Title('Аватарка', 'avatar'),
    ]
    public function avatar (UserUploadAvatarRequest $request)
    {
        if ($request->input('remove')) {
            $this->user->clearMediaCollection('avatar');
            event(new UserRemoveAvatarEvent);
        } else {
            $media = match (true) {
                $request->file('image') !== null   => $this->user->addMediaFromRequest('image')->toMediaCollection('avatar'),
                $request->input('base64') !== null => $this->user->addMediaFromBase64($request->input('base64'), ['image/*'])->toMediaCollection('avatar'),
                $request->input('url') !== null    => $this->user->addMediaFromUrl($request->input('url'), ['image/*'])->toMediaCollection('avatar'),
                default                            => false
            };

            event(new UserUploadAvatarEvent(data: $media));
        }
        return $this->send(true);
    }


    #[
        Title('Добавить/изменить адрес', 'address'),
    ]
    public function address (UserUpsertAddressRequest $request)
    {
        $address_location = [];
        if ($request['address_location']['lat'] ?? false) {
            $address_location = ['address_location' => new Point($request['address_location']['lat'], $request['address_location']['lng'])];
        }

        $dadata = DaDataAddress::geolocate($request['address_location']['lat'], $request['address_location']['lng'], 1);
        $extra = $dadata['suggestions'][0]['data'] ?? null;
        if ($extra) {
            $address_location['address_full'] = $dadata['suggestions'][0]['unrestricted_value'];
        }

        UserAddress::withTrashed()
            ->updateOrCreate([
                'uuid' => $request->input('uuid')
            ], [
                'user_id'    => $this->user->id,
                'deleted_at' => null,
                ...$request->safe()->only([
                    'name',
                    'address',
                    'address_full',
                    'flat',
                    'entrance',
                    'floor',
                    'default'
                ]),
                'extra'      => $extra,
                ...$address_location
            ]);

        if ($request->input('default')) {
            $this->user->addresses()->where('uuid', '!=', $request->input('uuid'))->update(['default' => false]);
        }

        event(new UserUpsertAddressEvent(data: $request->safe()->toArray()));
        $this->user->load($this->relations);
        return $this->send(new UserResource($this->user));
    }

    #[
        Title('Удалить адрес', 'remove_address'),
    ]
    public function remove_address (UserAddress $user_address)
    {
        if ($user_address->user_id !== $this->user->id) {
            throw new AccessDeniedException;
        }
        $user_address->delete();
        event(new UserRemoveAddressEvent(data: $user_address));
        $this->user->refresh();
        $this->user->load($this->relations);
        return $this->send(new UserResource($this->user));
    }

    #[
        Title('Добавить/изменить оплату', 'payment'),
    ]
    public function payment (UserUpsertPaymentRequest $request)
    {
        $data = null;
        switch ($request->input('method')) {
            case 'creditcard':
            {
                break;
            }
        }
        UserPayment::withTrashed()
            ->updateOrCreate([
                'uuid' => $request->input('uuid')
            ], [
                'user_id'    => $this->user->id,
                'deleted_at' => null,
                ...$request->safe()->only([
                    'name',
                    'method',
                    'default'
                ]),
                'data'       => $data
            ]);

        if ($request->input('default')) {
            $this->user->payments()->where('uuid', '!=', $request->input('uuid'))->update(['default' => false]);
        }

        event(new UserUpsertPaymentEvent(data: $request->safe()->toArray()));
        $this->user->load($this->relations);
        return $this->send(new UserResource($this->user));
    }

    #[
        Title('Удалить метод оплаты', 'remove_payment'),
    ]
    public function remove_payment (UserPayment $user_payment)
    {
        if ($user_payment->user_id !== $this->user->id) {
            throw new AccessDeniedException;
        }
        $user_payment->delete();
        event(new UserRemoveAddressEvent(data: $user_payment));
        $this->user->refresh();
        $this->user->load($this->relations);
        return $this->send(new UserResource($this->user));
    }


    #[
        Title('Добавить PushToken', 'create_push_token'),
    ]
    public function create_push_token (UserCreatePushToken $request)
    {
        if (!$this->user->push_tokens()->where('token', $request->input('token'))->count()) {
            $this->user->push_tokens()->create($request->safe()->only([
                'token',
                'token_type'
            ]));
        }
        return $this->send(new UserResource($this->user));
    }


    #[
        Title('Удалить PushToken', 'remove_push_token'),
    ]
    public function remove_push_token (UserRemovePushToken $request)
    {
        UserPushToken::where('user_id', $this->user->id)->where('token', $request->input('token'))->delete();
        return $this->send(new UserResource($this->user));
    }

    #[
        Title('Пример пуша', 'push_sample'),
    ]
    public function push_sample (UserSamplePushRequest $request)
    {
        [$notify, $model] = match ($request->input('type')) {
            'done'     => [OrderDoneNotification::class, Order::where('uuid', $request->input('id'))->firstOrFail()],
            'cancel'   => [OrderCancelNotification::class, Order::where('uuid', $request->input('id'))->firstOrFail()],
            'delivery' => [
                OrderDeliveryOnWayNotification::class, Order::where('uuid', $request->input('id'))->firstOrFail()
            ],
            default    => [false, false]
        };
        if ($notify) {
            $model->user->notify(new $notify($model));
        }
        return $this->send(true);
    }

    #[
        Title('Промокоды', 'promo'),
    ]
    public function promo (UserPromoRequest $request)
    {
        $listPromos = collect();
        $userPromos = UserPromo::where('user_id', $this->user->id)->with('promo')->get();

        if (!$this->user->orders()->count() && $this->user->media_source !== 'CPA-Getblogger') {
            $promo = Promo::where('code', 'ХОЧУ200')->first();
            if ($promo) $listPromos->push($promo);
        }

        return $this->send([
            'count' => [
                'delivery' => UserPromo::where('user_id', $this->user->id)->whereRelation('promo', function ($query) {
                    $query->where('type', 'delivery');
                })->count(),
                'money'    => UserPromo::where('user_id', $this->user->id)->whereRelation('promo', function ($query) {
                    $query->where('type', 'value');
                })->count()
            ],
            'list'  => UserPromoResource::collection($userPromos),
            'list_public' => PromoResource::collection($listPromos)
        ]);
    }

    #[
        Title('Получить список карт пользователя', 'payment_cards'),
    ]
    public function paymentCards(PaymentInterface $paymentService)
    {
        $list = $paymentService->getCardList($this->user->uuid);
//        $list = [
//            [
//                "CardId" => "334005568",
//                "Pan" => "430000******0777",
//                "Status" => "A",
//                "RebillId" => "1692929909050",
//                "CardType" => 0,
//                "ExpDate" => "1122",
//                "isMain" => false
//            ],
//            [
//                "CardId" => "334005568",
//                "Pan" => "430000******0776",
//                "Status" => "A",
//                "RebillId" => "1692929909050",
//                "CardType" => 0,
//                "ExpDate" => "1122",
//                "isMain" => false
//            ],
//        ];
        $list = collect($list);
        $activeCards = $list->filter(fn($cart) => $cart['Status'] === 'A');
        if ($activeCards->isNotEmpty()) {
            $activeCards = $activeCards->map(function ($item, int $index) {
                if ($index === 1) {
                    $item['isMain'] = true;
                } else {
                    $item['isMain'] = false;
                }
                return $item;
            });
        }
        return $this->send($activeCards);
    }

    #[
        Title('Удалить карту', 'deleteCard'),
    ]
    public function deleteCard(string $cardId, PaymentInterface $paymentService)
    {
        $result = $paymentService->removeCard($cardId);
        return $this->send($result);
    }

    public function saveAfCallbackData(Request $request)
    {
        // TODO create or update
        AfCallbackData::create([
            'user_id' => $request->user()->id,
            'data' => $request->input('data'),
        ]);
        return $this->send(true);
    }

    public function saveAfCallbackDataTemp(Request $request)
    {
        // TODO create or update
        AfCallbackDataTemp::create([
            'user_id' => $request->user()->id,
            'data' => $request->input('data'),
        ]);
        return $this->send(true);
    }

    public function saveAfEvent(Request $request)
    {
        AfEvent::create([
            'user_id' => $request->user()->id,
            'type' => $request->input('type'),
            'payload' => $request->input('data'),
        ]);
        return $this->send(true);
    }
}
