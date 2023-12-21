<?php

namespace App\Providers;

use App\Jobs\SDG\SDGProcessARVJob;
use App\Jobs\SDG\SDGProcessSHPJob;
use App\Jobs\SDG\SDGProcessWBLJob;
use App\Jobs\SDG\SDGSendInboundJob;
use App\Jobs\SDG\SDGSendMatMasterJob;
use App\Jobs\SDG\SDGSendOutboundJob;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    protected function authorization () {}

    public function register ()
    {
        Telescope::night();

        $this->hideSensitiveRequestDetails();

        Telescope::filter(function (IncomingEntry $entry) {

            // если ошибка, то точно постим
            if ($entry->isReportableException() ||
                $entry->isFailedRequest() ||
                $entry->isFailedJob() ||
                $entry->isScheduledTask() ||
                $entry->hasMonitoredTag()) return true;

            // иначе фильтруем
            switch ($entry->type) {
                case EntryType::REQUEST:
                {
                    $uri = $entry->content['uri'];
                    return match (true) {
                        str_starts_with($uri, '/user/'),
                        str_starts_with($uri, '/payment/'),
                        str_starts_with($uri, '/external/'),
                        str_starts_with($uri, '/kkm/'),
                        str_starts_with($uri, '/file/'),
                            //str_starts_with($uri, '/nova'),
                        str_starts_with($uri, '/provider/') => true,
                        default                             => false
                    };
                }
                case EntryType::GATE:
                {
                    $ability = $entry->content['ability'];
                    return match (true) {
                        str_ends_with($ability, 'viewNova') => false,
                        default                             => true
                    };
                }
                case EntryType::LOG:
                {
                    $message = $entry->content['message'];
                    return match (true) {
                        str_starts_with($message, 'Return type of ZBateson\\MailMimeParser\\Header\\HeaderContainer::getIterator') => false,
                        default                                                                                                    => true
                    };
                }
                case EntryType::JOB:
                {
                    $name = $entry->content['name'];
                    return match ($name) {
                        SDGSendInboundJob::class,
                        SDGProcessARVJob::class,
                        SDGProcessSHPJob::class,
                        SDGProcessWBLJob::class,
                        SDGSendMatMasterJob::class,
                        SDGSendOutboundJob::class => true,
                        default                   => false
                    };
                    return false;
                }
                case EntryType::EVENT:
                {
                    return false;
                }
            }


            return true;
        });

        Telescope::tag(function (IncomingEntry $entry) {
            $result = [];

            if ($entry->user) {
                $result[] = class_basename($entry->user);
                $result[] = class_basename($entry->user) . ':' . $entry->user->id;
            }

            if ($entry->type === EntryType::CLIENT_REQUEST) {
                $result[] = 'http:' . $entry->content['response_status'];
                if ($entry->content['response_status'] >= 400) {
                    $result[] = 'failed';
                }
            }

            if ($entry->type === EntryType::JOB) {
                $result[] = 'job:' . class_basename($entry->content['name']);
            }

            if ($entry->type === EntryType::REQUEST) {
                $uri = parse_url($entry->content['uri'], PHP_URL_PATH);
                $result[] = 'uri:' . $uri;
            }

            if ($entry->type === EntryType::LOG) {
                $level = $entry->content['level'];
                $result[] = 'level:' . $level;
            }

            return $result;
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     *
     * @return void
     */
    protected function hideSensitiveRequestDetails ()
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     *
     * @return void
     */
    protected function gate ()
    {
        Gate::define('viewTelescope', function ($user) {
            return in_array($user->email, [
                //
            ]);
        });
    }
}
