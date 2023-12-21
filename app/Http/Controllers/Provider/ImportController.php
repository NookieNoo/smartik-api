<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\ApiController;
use App\Services\HttpParser;
use Illuminate\Http\Request;

class ImportController extends ApiController {
    public function import (Request $request, HttpParser $parser) {
        //$data = $parser->fromRequest($request);
        return $this->send($request->all());
    }
}