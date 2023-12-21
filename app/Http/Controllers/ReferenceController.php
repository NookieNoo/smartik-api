<?php

namespace App\Http\Controllers;

use App\Services\ActiveApi\Attributes\Position;
use App\Services\ActiveApi\Attributes\Title;

#[
    Title('Справочники', 'reference'),
    Position(1)
]
class ReferenceController extends ApiController
{
    #[
        Title('Версия ПО', 'version')
    ]
    public function minimum ()
    {
        return $this->send([
            'ios'     => [
                'now'            => config('app.mobile.ios.current'),
                'minimum'        => config('app.mobile.ios.minimum'),
                'marker'         => config('app.mobile.ios.market'),
                'needExpoUpdate' => config('app.mobile.ios.need_expo_update')
            ],
            'android' => [
                'now'            => config('app.mobile.android.current'),
                'minimum'        => config('app.mobile.android.minimum'),
                'marker'         => config('app.mobile.android.market'),
                'needExpoUpdate' => config('app.mobile.android.need_expo_update')
            ]
        ]);
    }
}
