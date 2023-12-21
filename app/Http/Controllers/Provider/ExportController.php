<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\ApiController;
use App\Services\HttpParser;
use Illuminate\Http\Request;

class ExportController extends ApiController
{
    public function export ()
    {
        return $this->send(
            [
                [
                    'BARCODE'       => 4660001445420,
                    'PRODUCTIODATE' => '03.11.2022',
                    'COUNT'         => 15
                ],
                [
                    'BARCODE'       => 4660001446052,
                    'PRODUCTIODATE' => '17.11.2022',
                    'COUNT'         => 10
                ]
            ]
        );
    }

    public function order (Request $request, HttpParser $parser)
    {
        //$data = $parser->fromRequest($request);
        return $this->send($request->all());
    }
}