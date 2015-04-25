<?php

namespace Tokenly\LaravelEventLog;

use Illuminate\Support\Facades\Log;

class TestingEventLog extends EventLog {

    protected $logged_events = [];

    public function log($event, $raw_data, $array_keys_only=null, $other_columns=null) {
        $this->logged_events[] = ['event' => $event, 'data' => $raw_data, ];

        return parent::log($event, $raw_data, $array_keys_only, $other_columns);
    }

    public function getLoggedEvents() {
        return $this->logged_events;
    }
    public function clearLoggedEvents() {
        $this->logged_events = [];
    }

}
