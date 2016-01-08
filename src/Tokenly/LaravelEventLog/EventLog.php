<?php

namespace Tokenly\LaravelEventLog;


use Exception;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class EventLog {

    protected $write_json      = false;
    protected $json_log_path   = null;
    protected $json_log_stream = null;

    protected $levels = [
        'debug'     => 100,
        'info'      => 200,
        'notice'    => 250,
        'warning'   => 300,
        'error'     => 400,
        'critical'  => 500,
        'alert'     => 550,
        'emergency' => 600,
    ];

    public function __construct($json_log_path=null) {
        $this->write_json    = ($json_log_path !== null);
        $this->json_log_path = $json_log_path;
    }


    public function debug($event, $raw_data, $array_filter_keys=null) {
        return $this->log($event, $raw_data, $array_filter_keys, 'debug');
    }

    public function info($event, $raw_data, $array_filter_keys=null) {
        return $this->log($event, $raw_data, $array_filter_keys, 'info');
    }

    public function warning($event, $raw_data, $array_filter_keys=null) {
        return $this->log($event, $raw_data, $array_filter_keys, 'warning');
    }

    public function error($event, $raw_data, $array_filter_keys=null) {
        return $this->log($event, $raw_data, $array_filter_keys, 'error');
    }

    public function critical($event, $raw_data, $array_filter_keys=null) {
        return $this->log($event, $raw_data, $array_filter_keys, 'critical');
    }

    public function jsonLog($level_name, $event, $raw_data=[], $array_filter_keys=null) {
        return $this->log($event, $raw_data, $array_filter_keys, $level_name, false, true);
    }

    public function log($event, $raw_data, $array_filter_keys=null, $level_name=null, $as_text=true, $as_json=true) {
        if ($level_name === null) { $level_name = 'info'; }
        try {
            $data = $this->filterLogData($raw_data, $array_filter_keys);

            // write to laravel log
            if ($as_text) {
                Log::log($level_name, $this->buildLogText($event, $data));
            }

            // write to json log
            if ($this->write_json AND $as_json) {
                $this->writeToJSONLog($this->buildLogJSON($level_name, $event, $data));
            }

        } catch (Exception $e) {
            if ($e instanceof RuntimeException) {
                $msg = "RuntimeException for event $event in ".$e->getFile()." at line ".$e->getLine();
            } else {
                $msg = "Exception (".$e->getCode().") for event $event ".$e->getMessage()." in ".$e->getFile()." at line ".$e->getLine();
            }

            // log error
            Log::error($msg);

            // write to json log
            if ($this->write_json) {
                $this->writeToJSONLog($this->buildLogJSON('error', 'error.log', ['msg' => $msg, 'code' => $e->getCode(), 'line' => $e->getLine(), 'file' => $e->getFile()]));
            }
        }
    }

    public function logError($event, $error_or_data, $additional_error_data=null) {
        if ($error_or_data instanceof Exception) {
            $e = $error_or_data;
            $raw_data = [
                'error' => $e->getMessage(),
                'code'  => $e->getCode(),
                'line'  => $e->getLine(),
                'file'  => $e->getFile(),
            ];
        } else {
            $raw_data = $error_or_data;
        }

        // merge extra data
        if ($additional_error_data !== null) {
            $raw_data = array_merge($raw_data, $additional_error_data);
        }

        Log::error($this->buildLogText($event, $raw_data));

        // write to json log
        if ($this->write_json) {
            $this->writeToJSONLog($this->buildLogJSON('error', $event, $raw_data));
        }
    }

    // ------------------------------------------------------------------------

    protected function buildLogText($event, $data) {

        return $event." ".str_replace('\n', "\n", json_encode($data, 192));
    }

    protected function filterLogData($raw_data, $array_filter_keys=null) {
        if ($array_filter_keys !== null) {
            $filtered_data = [];
            foreach($array_filter_keys as $array_key) {
                $filtered_data[$array_key] = $raw_data[$array_key];
            }
        } else {
            if (is_array($raw_data)) {
                $filtered_data = $raw_data;
            } else if (is_object($raw_data)) {
                $filtered_data = json_decode(json_encode($raw_data), true);
                if (!$filtered_data) { throw new Exception("Unable to decode object of type ".get_class($raw_data), 1); }
            } else {
                // assume raw_data is just a string
                $filtered_data = ['msg' => (string) $raw_data];
            }
        }

        return $filtered_data;
    }

    protected function buildLogJSON($level_name, $event, $data) {
        $json = array_merge([
            'name'  => $event,
            'ts'    => intval(microtime(true) * 1000),
            'level' => isset($this->levels[$level_name]) ? $this->levels[$level_name] : 0,
            'event' => $data,
        ], $data);

        return $json;
    }

    protected function writeToJSONLog($json_data) {
        if (!is_resource($this->json_log_stream)) {
            $this->json_log_stream = $this->openJSONLog($this->json_log_path);
        }

        fwrite($this->json_log_stream, json_encode($json_data, JSON_FORCE_OBJECT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    protected function openJSONLog($json_log_path) {
        $stream = fopen($json_log_path, 'a');
        return $stream;
    }

}
