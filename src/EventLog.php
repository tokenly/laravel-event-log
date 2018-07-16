<?php

namespace Tokenly\LaravelEventLog;


use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tokenly\LaravelEventLog\Util\ExceptionTraceBuilder;

class EventLog {

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

    public function __construct() {
        $this->log_writer = Log::getFacadeRoot();
    }

    public function setLogWriter($log_writer)
    {
        $this->log_writer = $log_writer;
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

    // alias of warning
    public function warn($event, $raw_data, $array_filter_keys=null) { return $this->warning($event, $raw_data, $array_filter_keys); }

    public function error($event, $raw_data, $array_filter_keys=null) {
        return $this->log($event, $raw_data, $array_filter_keys, 'error');
    }

    public function critical($event, $raw_data, $array_filter_keys=null) {
        return $this->log($event, $raw_data, $array_filter_keys, 'critical');
    }

    public function log($event, $raw_data, $array_filter_keys=null, $level_name=null) {
        if ($level_name === null) { $level_name = 'info'; }
        try {
            $data = $this->filterLogData($raw_data, $array_filter_keys);

            // write to laravel log
            $this->log_writer->log($level_name, $this->buildLogText($event, $data));
        } catch (Exception $e) {
            if ($e instanceof RuntimeException) {
                $msg = "RuntimeException for event $event in ".$e->getFile()." at line ".$e->getLine();
            } else {
                $msg = "Exception (".$e->getCode().") for event $event ".$e->getMessage()." in ".$e->getFile()." at line ".$e->getLine();
            }

            // log error
            $this->log_writer->error($msg);
        }
    }

    public function logError($event, $error_or_data, $additional_error_data=null) {
        if ($error_or_data instanceof Exception) {
            $e = $error_or_data;
            if ($e instanceof HttpResponseException) {
                $err_json = json_decode($e->getResponse()->getContent(), true);
                if (is_array($err_json)) {
                    $error_message = isset($err_json['message']) ? $err_json['message'] : (isset($err_json['error']) ? $err_json['error'] : $e->getResponse()->getContent());
                } else {
                    $error_message = $e->getResponse()->getContent();
                }
            } else {
                $error_message = $e->getMessage();
            }

            $builder = app(ExceptionTraceBuilder::class);
            $original_exception = $builder->getOriginalException($e);

            $raw_data = [
                'error' => $error_message,
                'code'  => $original_exception->getCode(),
                'line'  => $original_exception->getLine(),
                'file'  => $original_exception->getFile(),
                'trace' => $builder->buildShortTrace($e),
            ];
        } else {
            $raw_data = $this->filterLogData($error_or_data, null, 'error');
        }

        // merge extra data
        if ($additional_error_data !== null) {
            $raw_data = array_merge($raw_data, $additional_error_data);
        }

        $this->log_writer->error($this->buildLogText($event, $raw_data));
    }

    // ------------------------------------------------------------------------

    protected function buildLogText($event, $data) {
        return $event." ".json_encode($data, 192);
    }

    protected function filterLogData($raw_data, $array_filter_keys=null, $error_key='msg') {
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
                $filtered_data = [$error_key => (string) $raw_data];
            }
        }

        return $filtered_data;
    }

    protected function flatten($val) {
        if (!is_array($val)) { return $val; }

        $is_numeric = !(count(array_filter(array_keys($val), 'is_string')) > 0);
        if ($is_numeric) {
            $flattened = '';
            foreach($val as $child_val) {
                $flattened .= $this->flatten($child_val).", ";
            }
            $val = rtrim(rtrim($flattened), ',');
        } else {
            // multi-dimensional array - just use JSON
            $val = json_encode($val);
        }
        return $val;
    }


}
