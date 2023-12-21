<?php

namespace App\Exceptions\Integration\Imap;

use App\Exceptions\Integration\ImapException;

class ManyAttachmentsException extends ImapException
{
    public function __construct ()
    {
        parent::__construct(
            message: 'В письме несколько файлов, обрабатывать умеем только один',
        );
    }
}