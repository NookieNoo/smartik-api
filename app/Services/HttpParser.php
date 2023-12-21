<?php

namespace App\Services;

use App\Services\RoutingParser\HttpParserInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class HttpParser{
    public function __construct (
        private ?HttpParserInterface $parser = null
    ) {
    }

    public function fromRequest (Request $request): Collection {
        if (!$this->parser) return collect();
        return $this->parser->parse($request);
    }
}