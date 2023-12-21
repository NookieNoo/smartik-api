<?php

namespace App\Http\Controllers\User;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PromocodeType;
use App\Events\User\UserChangeStatusOrderEvent;
use App\Events\User\UserCreateOrderEvent;
use App\Exceptions\Custom\CantChangeOrderStatusException;
use App\Exceptions\Custom\CartIsNullException;
use App\Exceptions\Vendor\AccessDeniedException;
use App\Http\Controllers\ApiController;
use App\Http\Requests\User\UserCreateOrderRequest;
use App\Http\Resources\User\MinimalOrderResource;
use App\Http\Resources\User\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\ActiveApi\Attributes\Title;
use App\Services\Payment\PaymentInterface;
use App\Services\ShowcaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Rap2hpoutre\FastExcel\FastExcel;

#[
    Title('Заказы', 'order'),
]
class OrderController extends ApiController
{

    protected array $relations = [
        'address',
        'payment',
        'cart' => [
            'products'
        ],
        'checks'
    ];

    #[
        Title('Создание', 'create'),
    ]
    public function create (UserCreateOrderRequest $request, ShowcaseService $showcase, PaymentInterface $paymentService)
    {
        //exit;
        // Ругаемся, если корзина пустая
        $cart = $this->user->cart;
        if (!$cart) {
            throw new CartIsNullException;
        }

        // Создаём заказ из корзины, если ещё не
        $order = Order::where('cart_id', $cart->id)->where('user_id', $this->user->id)->first();
        if (!$order) {
            $order = $showcase->cart($cart)->publish($request->input('comment'), $request->input('time_delivery_at'));
            event(new UserCreateOrderEvent(data: $order));
        }

        // Обновляем сумму. todo избавиться нахер
        $cast = $cart->cast();
        $order->update([
            'sum_final'    => $cast->sumFinal,
            'sum_products' => $cast->sumProducts,
        ]);

        if ($order->payment?->status !== PaymentStatus::HOLD) {
            $paymentService->hold($order);
        }

        // Подгружаем всё и выводим
        $order->load($this->relations);

        return $this->send(new OrderResource($order));
    }

    #[
        Title('Инфо', 'info'),
    ]
    public function info (Order $order)
    {
        if ($order->user_id !== $this->user->id) {
            throw new AccessDeniedException;
        }
        $order->load($this->relations);
        return $this->send(new OrderResource($order));
    }

    #[
        Title('Отмена', 'cancel'),
    ]
    public function cancel (Order $order)
    {
        if ($order->user_id !== $this->user->id) {
            throw new AccessDeniedException;
        }
        if (in_array($order->status, [
            OrderStatus::DELIVERY_ON_WAY, OrderStatus::DELIVERY_ARRIVED, OrderStatus::DONE
        ])) {
            throw new CantChangeOrderStatusException($order->status);
        }
        $order->update([
            'status' => OrderStatus::CANCELED_USER
        ]);
        event(new UserChangeStatusOrderEvent($order, extra: ['status' => OrderStatus::CANCELED_USER]));
        $order->load($this->relations);
        return $this->send(new OrderResource($order));
    }

    #[
        Title('Список', 'list'),
    ]
    public function list (string $uuid = "")
    {
        $query = Order::where('user_id', $this->user->id);
        if ($uuid) {
            $order = Order::where('uuid', $uuid)->where('user_id', $this->user->id)->first();
            if ($order) {
                $query->where('id', '<', $order->id);
            }
        }
        $query->orderBy('id', 'DESC')->limit(20);
        $orders = $query->get();

        return $this->send(MinimalOrderResource::collection($orders));
    }

    #[
        Title('Список статусов заказа', 'order_statuses'),
    ]
    public function orderStatuses ()
    {
        return $this->send(OrderStatus::titles());
    }

    #[
        Title('Отчет по продажам', 'sold_orders_report'),
    ]
    public function soldOrdersReport(Request $request)
    {
        ini_set('memory_limit', '500M'); //FIXME
        $start = $request->query('start');
        $finish = $request->query('finish');
        $result = [[
            'ID заказа',
            'Имя заказа',
            'Дата создания заказа',
            'Дата доставки заказа',
            'Пользователь,id',
            'ID товара',
            'Товар',
            'Заморозка',
            'Цена',
            'Цена без промо',
            'Количество',
            'РРЦ',
            'Цена для Смартика',
            'Сумма',
            'НДС',
            'Промокод',
        ]];

        $query = Order::where('status', 'done');
        if (!empty($start)) {
            $query->where('created_at', '>=', $start);
        }
        if (!empty($finish)) {
            $query->where('created_at', '<=', $finish);
        }

        $orders = $query
            ->each(function ($order) use (&$result, &$count) {
                $count++;
                $cast = $order->cart->cast();
                $check = 0;
                $cast->products->where('canceled', false)->each(function ($item) use (&$result, &$check, $order) {
                    // dd(ProductPrice::find($item->id));
                    $productPrice = ProductPrice::find($item->id);
                    $product = Product::find($item->product_id);
                    $check = $check + $item->price*$item->count;
                    $result[] = [
                        $order->id,
                        $order->name,
                        $order->created_at->format('Y-m-d H:i:s'),
                        $order->updated_at->format('Y-m-d H:i:s'),
                        $order->user_id,
                        // ProductPrice::find($item->id)->product_id,
                        $productPrice->product_id,
                        str_replace('"', '""', $item->name),
                        $product->is_frozen ? 'Да' : 'Нет',
                        number_format($item->price, 2, ',', ''),
                        $item->priceWithoutPromo,
                        $item->count,
                        $productPrice->start_price,
                        $productPrice->finish_price,
                        number_format($item->price*$item->count, 2, ',', ''),
                        $item->vat,
                        $order->cart?->promos?->last()->code ?? '',
                    ];
                });
                if ($cast->deliveryPriceFinal) {
                    $check = $check + (float)$cast->deliveryPriceFinal;
                    $result[] = [
                        $order->id,
                        $order->name,
                        $order->created_at->format('Y-m-d H:i:s'),
                        $order->updated_at->format('Y-m-d H:i:s'),
                        $order->user_id,
                        0,
                        'Доставка',
                        '',
                        $cast->deliveryPriceFinal,
                        0,
                        1,
                        "",
                        "",
                        $cast->deliveryPriceFinal,
                        20,
                        ''
                    ];
                }

                $check = (float)number_format($check, 2, '.', '');

                if (abs($check - $cast->sumFinal)>1) {
                    Log::debug('Incorrect calculation in export orders', [
                        '$check'  => $cast->sumFinal,
                        '$cast->sumFinal' => $cast->sumFinal,
                        'order_id' => $order->id
                    ]);
                    dump($cast);exit;
                }
            });

        return (new FastExcel($result))
            ->withoutHeaders()
            ->download(date('Y-m-d') . "_sold_report.csv");
    }
}
