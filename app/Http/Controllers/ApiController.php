<?php

namespace App\Http\Controllers;

use App\Http\Responses\ErrorResponse;
use App\Http\Responses\Response;
use App\Services\ActiveApi\Attributes\BadResponseWrapper;
use App\Services\ActiveApi\Attributes\ResponseWrapper;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Routing\Controller;

class ApiController extends Controller
{
    public ?Authenticatable $user      = null;
    public bool             $isAdmin   = false;
    protected array         $relations = [];

    public function __construct ()
    {
        $this->user = auth()->user();
        $this->isAdmin = request()->ip() === '91.218.85.179' || request()->ip() === '92.244.246.84';
    }

    #[ResponseWrapper]
    protected function send (mixed $data, int $code = 200, ?array $meta = null)
    {
        return Response::send($data, $code, $meta);
    }

    #[BadResponseWrapper]
    protected function error ($error, int $code = 422)
    {
        return ErrorResponse::send($error, $code);
    }

    protected function sendRaw(mixed $data, int $code = 200)
    {
        return Response::sendRaw($data, $code);
    }
}
