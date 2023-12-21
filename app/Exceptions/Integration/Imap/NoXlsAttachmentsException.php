<?php

namespace App\Exceptions\Integration\Imap;

use App\Exceptions\Integration\ImapException;

class NoXlsAttachmentsException extends ImapException
{
    public function __construct ()
    {
        parent::__construct(
            message: 'Поддерживаются только файлы типа .xls и .xlsx',
        );
    }
}