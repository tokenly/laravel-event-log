<?php

namespace Tokenly\LaravelEventLog;

use Illuminate\Support\ServiceProvider;
use Tokenly\LaravelEventLog\EventLog;

class EventLogServiceProvider extends ServiceProvider {


    public function register() {
        $this->app->bind('eventlog', function($app) {
            return new EventLog();
        });

    }


}
