<?php

namespace Tokenly\LaravelEventLog;

use Illuminate\Support\ServiceProvider;
use Tokenly\LaravelEventLog\EventLog;

class EventLogServiceProvider extends ServiceProvider {


    public function register() {
        $this->app->bind('eventlog', function($app) {
            $json_log_path = null;
            if (env('USE_JSON_LOG', true)) {
                $json_log_path = $app->storagePath().'/logs/'.env('JSON_LOG_NAME', 'log.json');
            }

            return new EventLog($json_log_path);
        });


        $this->app->bind('api.logApiCalls', function($app) {
            return $app->make('Tokenly\LaravelEventLog\Middleware\LogAPICalls');
        });


    }


}
