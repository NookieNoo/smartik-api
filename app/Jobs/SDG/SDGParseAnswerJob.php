<?php

namespace App\Jobs\SDG;

use App\Services\SDGTransport;
use Carbon\Carbon;

class SDGParseAnswerJob
{
    public function __construct () {}

    public function handle (SDGTransport $transport)
    {
        $dir = config('sdg.ftp.dir') . '/Out/';
        $list = $transport->ftp_nlist($dir);

        foreach ($list as $file) {
            if (strtolower(substr($file, -4, 4)) === '.xml') {
                $file = substr($file, strlen($dir));

                $copy_to = storage_path('app/integration/sdg/') . Carbon::now()->format('Y/m/d') . '/' . $file;
                $copy_from = $dir . $file;

                if (!file_exists(storage_path('app/integration/sdg/') . Carbon::now()->format('Y/m/d'))) {
                    mkdir(storage_path('app/integration/sdg/') . Carbon::now()->format('Y/m/d'), 0777, true);
                }
                $transport->ftp_get($copy_to, $copy_from);

                $process = match (true) {
                    str_starts_with($file, 'ARV') => SDGProcessARVJob::class,
                    str_starts_with($file, 'SHP') => SDGProcessSHPJob::class,
                    str_starts_with($file, 'WBL') => SDGProcessWBLJob::class,
                    default                       => false
                    //str_starts_with($file, 'WBL') => 'App\\Jobs\\SDG\\SDGProcessWBLJob',
                };

                if ($process) {
                    dispatch(new $process($copy_to, $copy_from));
                }
            }
        }
    }
}