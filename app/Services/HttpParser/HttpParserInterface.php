<?php

namespace App\Services\HttpParser;

use Illuminate\Http\Request;

interface HttpParserInterface
{
    public function parse (Request $request);
}