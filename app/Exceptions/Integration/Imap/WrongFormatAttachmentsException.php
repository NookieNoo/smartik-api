<?php

namespace App\Exceptions\Integration\Imap;

use App\Exceptions\Integration\ImapException;

class WrongFormatAttachmentsException extends ImapException
{
    public function __construct ()
    {
        parent::__construct(
            message: 'Загруженный файл имеет неизвестный формат. Скорее всего неверно названы столбцы или изменён их порядок',
        );
    }
}