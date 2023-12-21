<?php

namespace App\Jobs\SDG;

use App\Services\SDGTransport;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SDGJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries   = 5;
    public $backoff = [
        1 * 60,
        5 * 60,
        10 * 60,
        15 * 60,
        20 * 60
    ];

    protected SDGTransport $transport;
    protected string       $filename;
    protected string       $filepath;
    protected string       $type;
    protected string       $content;

    public function __construct (
        public bool          $send = true,
        public Carbon|string $time = new Carbon(),
        public mixed         $extra = null
    )
    {
        if (is_string($time)) {
            $this->time = Carbon::parse($time);
        }
    }

    protected function prepareFile (?string $postfix = null)
    {
        $this->filename = $this->type . '_' . Carbon::now()->format('YmdHi') . '_' . rand(1000, 9999) . ($postfix ? "_$postfix" : '') . '.xml';
    }

    protected function saveFile ()
    {
        $dir = storage_path('app/integration/sdg') . '/' . $this->time->format('Y/m/d');
        $this->filepath = $dir . '/' . $this->filename;

        if (!file_exists($dir)) mkdir($dir, recursive: true);
        file_put_contents($this->filepath, $this->content);
    }

    protected function sendFile ()
    {
        if ($this->send) {
            $this->transport->ftp_put(config('sdg.ftp.dir') . '/' . $this->filename, $this->filepath);
        }
    }

    public function handle ()
    {
        try {
            $this->transport = new SDGTransport();
            $this->content = $this->generate();

            if ($this->content) {
                $this->prepareFile();
                $this->saveFile();
                $this->sendFile();
            }
        } catch (\Throwable $e) {
            $this->fail($e);
        }
    }

    public function generate (): string|false {}
}
