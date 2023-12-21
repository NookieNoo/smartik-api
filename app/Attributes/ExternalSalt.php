<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ExternalSalt
{
    // Используется для хардкода соли внешних сервисов
    public function __construct (
        private string $salt,
    ) {}
}