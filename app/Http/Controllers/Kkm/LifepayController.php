<?php

namespace App\Http\Controllers\Kkm;

use App\Http\Controllers\ApiController;
use App\Models\KkmCheck;
use App\Services\Payment\PaymentInterface;
use Illuminate\Http\Request;

class LifepayController extends ApiController
{
    public function webhook (Request $request)
    {
        $data = json_decode($request->input('data'));

        KkmCheck::where('uuid', $data->uuid)->update(['result' => $request->input('data')]);

        return $this->send(true);
    }
}