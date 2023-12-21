<?php

namespace App\Jobs\Imap;

use BeyondCode\Mailbox\InboundEmail;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webklex\IMAP\Facades\Client as ImapClient;

class ImapCheckMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct () {}

    public function handle ()
    {
        $client = ImapClient::account('default');
        $client->connect();

        $folder = $client->getFolder('INBOX');

        $recentMessages = $folder
            ->query()
            ->since(Carbon::now()->subHours(2))
            ->get();

        $i = 0;
        foreach ($recentMessages as $message) {
            $inboundEmail = InboundEmail::fromMessage($message->getHeader() . $message->getRawBody());
            if (!InboundEmail::where('message_id', $inboundEmail->message()->getHeaderValue('Message-Id'))->exists()) {
                $inboundEmail->save();
                //Mailbox::callMailboxes($inboundEmail);
                dispatch(new ImapProcessCheckMailJob($inboundEmail))->delay(now()->addSeconds($i * 2));
                $i++;
            }
        }
    }
}