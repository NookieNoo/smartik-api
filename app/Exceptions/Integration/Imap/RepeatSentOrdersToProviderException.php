<?php

namespace App\Exceptions\Integration\Imap;

use App\Exceptions\Integration\ImapException;
use App\Models\IntegrationReport;

class RepeatSentOrdersToProviderException extends ImapException
{
    public function __construct (IntegrationReport $report)
    {
        parent::__construct(
            message: 'Попытка повторной отправки заказа поставщику',
            data: $report
        );
    }
}