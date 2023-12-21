<?php

namespace App\Exceptions\Integration\Imap;

use App\Exceptions\Integration\ImapException;

class NoAttachmentsException extends ImapException
{
    public function __construct ()
    {
        parent::__construct(
            message: 'В письме отсутствуют аттачи',
        );
    }
}