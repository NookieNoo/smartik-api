<?php

namespace App\Services\Integration\Transport\Mailable;

use App\Services\Integration\Transport\Enums\ImapXlsType;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;

class SuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct (
        public string      $provider,
        public Carbon      $date,
        public ImapXlsType $type,
        public array       $report
    ) {}


    public function content ()
    {
        return new Content(
            markdown: 'emails.integration.imap-success',
            with: [
                'provider'  => $this->provider,
                'date'      => $this->date->format('d.m.Y'),
                'isPrices'  => $this->type === ImapXlsType::PRICES,
                'isCatalog' => $this->type === ImapXlsType::CATALOG,
                'typeName'  => $this->type->title(),
                'report'    => $this->report
            ],
        );
    }
}