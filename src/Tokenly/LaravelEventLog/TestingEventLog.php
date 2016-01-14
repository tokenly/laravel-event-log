<?php

namespace Tokenly\LaravelEventLog;

use Illuminate\Support\Facades\Log;

class TestingEventLog extends EventLog {

    protected $logged_events = [];

    public function log($event, $raw_data, $array_filter_keys=null, $level_name=null, $as_text=true, $as_json=true) {
        $this->logged_events[] = ['event' => $event, 'data' => $raw_data, ];

        return parent::log($event, $raw_data, $array_filter_keys, $level_name, $as_text, $as_json);
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
