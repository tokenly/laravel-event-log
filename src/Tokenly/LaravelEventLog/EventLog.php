<?php

namespace Tokenly\LaravelEventLog;


use Exception;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class EventLog {


    public function __construct() {
    }


    public function log($event, $raw_data, $array_keys_only=null) {
        try {
            // write to laravel log
            Log::info($this->buildLogText($event, $raw_data, $array_keys_only));
        } catch (RuntimeException $e) {
            Log::error("RuntimeException in ".$e->getFile()." at line ".$e->getLine());
        } catch (Exception $e) {
            // other error
            Log::error($e->getCode()." ".$e->getMessage()." in ".$e->getFile()." at line ".$e->getLine());
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
    }

    protected function buildLogText($event, $raw_data, $array_keys_only=null) {
        if ($array_keys_only) {
            $data = [];
            foreach($array_keys_only as $array_key) {
                $data[$array_key] = $raw_data[$array_key];
            }
        } else {
            $data = $raw_data;
        }

        return $event." ".str_replace('\n', "\n", json_encode($data, 192));
    }

}
