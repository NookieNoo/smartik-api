<?php

namespace App\Exceptions;

use App\Http\Responses\ErrorResponse;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    public function render ($request, Throwable $e)
    {
        if (in_array($request->host(), [config('nova.domain'), config('horizon.domain')])) {
            return parent::render($request, $e);
        }
        return ErrorResponse::exception($e);
    }
}
