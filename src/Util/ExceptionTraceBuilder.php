<?php

namespace Tokenly\LaravelEventLog\Util;

use Exception;

class ExceptionTraceBuilder
{

    public function getOriginalException(Exception $e) {
        while ($e) {
            // loop through previous exceptions
            $previous = $e->getPrevious();
            if (!$previous) {
                return $e;
            }
            $e = $previous;
        }
    }

    public function buildShortTrace(Exception $master_exception, $limit=6) {
        try {
            $out = '';

            $previous_count = 0;
            $e = $master_exception;
            while ($e) {
                if ($previous_count > 0) {
                    $out .= "\n"."--- Previous exception {$previous_count} ---"."\n";
                }
                $offset = 0;
                foreach ($e->getTrace() as $trace_entry) {
                    $json_encoded_args = json_encode($trace_entry['args'] ?? null);

                    $out = $out
                        .($offset == 0 ? '' : "\n")
                        .(isset($trace_entry['file']) ? basename($trace_entry['file']) : '[unknown file]').", "
                        .(isset($trace_entry['line']) ? $trace_entry['line'] : '[unknown line]').": "
                        .(isset($trace_entry['class']) ? $trace_entry['class'].'::' : '')
                        .$trace_entry['function']
                        ."(".substr($json_encoded_args, 0, 240).(strlen($json_encoded_args > 240) ? '...' : '').")";

                    if (++$offset >= $limit) { break; }
                }

                // loop through previous exceptions
                $e = $e->getPrevious();
                ++$previous_count;
            }

            return $out;
        } catch (Exception $e) {
            return "Error building trace: ".$e->getMessage();
        }
    }

}
