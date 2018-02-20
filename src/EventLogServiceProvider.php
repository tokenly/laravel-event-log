<?php

namespace Tokenly\LaravelEventLog;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Tokenly\LaravelEventLog\EventLog;

class EventLogServiceProvider extends ServiceProvider {


    public function register() {
        $this->app->bind('eventlog', function($app) {
            return new EventLog(Log::getFacadeRoot());
        });

        $this->app->bind('api.logApiCalls', function($app) {
            return $app->make('Tokenly\LaravelEventLog\Middleware\LogAPICalls');
        });
    }

}
