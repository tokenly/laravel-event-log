<?php

namespace Tokenly\LaravelEventLog;

use Illuminate\Support\Facades\Log;

class TestingEventLog extends EventLog {

    protected $logged_events = [];

    public function log($event, $raw_data, $array_keys_only=null, $other_columns=null) {
        $this->logged_events[] = ['event' => $event, 'data' => $raw_data, ];

        return parent::log($event, $raw_data, $array_keys_only, $other_columns);
    }

    public function logError($event, $error_or_data, $additional_error_data=null) {
        $this->logged_events[] = ['event' => $event, 'data' => $error_or_data, ];
        return parent::logError($event, $error_or_data, $additional_error_data);
    }

    public function getLoggedEvents() {
        return $this->logged_events;
    }
    public function clearLoggedEvents() {
        $this->logged_events = [];
    }

}
