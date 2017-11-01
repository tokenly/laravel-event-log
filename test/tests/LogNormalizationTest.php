<?php

use Illuminate\Log\Writer;
use Tokenly\LaravelEventLog\EventLog;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\TestCase;

/*
* 
*/
class LogNormalizationTest extends TestCase
{


    public function testNormalizeLog() {
        list($event_log, $el_params) = $this->initLog();

        // simple
        $event_log->debug('event.foo', 'hello world');
        $json_data = $this->readJsonFileData($el_params);
        PHPUnit::assertEquals('event.foo', $json_data['name']);

        // structured
        list($event_log, $el_params) = $this->initLog();
        $event_log->debug('foo.bar2', ['key1' => 'hello world', 'key2' => 'blah',]);
        $json_data = $this->readJsonFileData($el_params);
        PHPUnit::assertEquals('foo.bar2', $json_data['name']);
        PHPUnit::assertEquals('hello world', $json_data['event']['key1']);
        PHPUnit::assertEquals('blah', $json_data['event']['key2']);
    }



    // ------------------------------------------------------------------------

    protected function initLog() {
        $tmp_filename = tempnam(sys_get_temp_dir(), 'eventlog');
        $monolog_logger = new Monolog\Logger('testing');
        $test_handler = new Monolog\Handler\TestHandler();
        $monolog_logger->pushHandler($test_handler);
        $event_log = new EventLog(new Writer($monolog_logger));
        return [
            $event_log,
            [
                'handler' => $test_handler
            ]
        ];
    }

    protected function readJsonFileData($el_params) {
        $test_handler = $el_params['handler'];
        $records = $test_handler->getRecords();
        $raw = $records[0]['message'];
        $json_start_pos = strpos($raw, '{');
        $name = substr($raw, 0, $json_start_pos - 1);
        $event = json_decode(substr($raw, $json_start_pos), true);

        $json_data = [
            'name'  => $name,
            'event' => $event,
        ];
        return $json_data;
    }
}
