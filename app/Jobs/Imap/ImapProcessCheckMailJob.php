<?php

namespace App\Jobs\Imap;

use App\Services\Integration\SmartikIntegration;
use BeyondCode\Mailbox\InboundEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImapProcessCheckMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct (public InboundEmail $email) {}

    public function handle ()
    {
        [$prefix_mail, $domain_mail] = explode('@', config('imap.accounts.default.username'));

        preg_match('/^' . preg_quote($prefix_mail, '/') . '\+([^@]+)@' . preg_quote($domain_mail, '/') . '$/', $this->email->to()[0]->getValue(), $provider);
        if (count($provider) > 1) {
            $provider = $provider[1];

            SmartikIntegration::readMailbox($this->email, $provider);
        }
    }
}