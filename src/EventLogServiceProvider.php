<?php

namespace Tokenly\LaravelEventLog;

use Illuminate\Support\ServiceProvider;
use Tokenly\LaravelEventLog\EventLog;

class EventLogServiceProvider extends ServiceProvider {


    public function register() {
        $this->app->bind('eventlog', function($app) {
            return new EventLog(app('Illuminate\Log\Writer'));
        });

        $this->app->bind('api.logApiCalls', function($app) {
            return $app->make('Tokenly\LaravelEventLog\Middleware\LogAPICalls');
        });


    }


}
