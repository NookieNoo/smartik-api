<?php

namespace App\Services\Integration\Transport\Mailable;

use App\Services\Integration\Transport\Enums\ImapXlsType;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;

class OrderToProviderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct (
        public string $provider,
        public Carbon $date,
        public string $filename
    ) {
        $this->subject = "Заказ поставщику \"" . $this->provider . "\" на " . $this->date->format('d.m.Y') . ' (Смартик)';
    }

    public function content ()
    {

        return new Content(
            markdown: 'emails.integration.imap-order-to-provider',
            with: [
                'provider' => $this->provider,
                'date'     => $this->date->format('d.m.Y'),
            ],
        );
    }

    public function attachments ()
    {
        return [
            Attachment::fromPath($this->filename),
        ];
    }
}