<?php

use Illuminate\Log\Writer;
use Tokenly\LaravelEventLog\EventLog;
use \PHPUnit_Framework_Assert as PHPUnit;

/*
* 
*/
class LogNormalizationTest extends \PHPUnit_Framework_TestCase
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

        // name rewrite
        list($event_log, $el_params) = $this->initLog();
        $event_log->debug('foo.bar3', ['name' => 'rename me', 'key2' => 'blah',]);
        $json_data = $this->readJsonFileData($el_params);
        PHPUnit::assertEquals('foo.bar3', $json_data['name']);
        PHPUnit::assertEquals('rename me', $json_data['event']['originalName']);

        // integer cast
        list($event_log, $el_params) = $this->initLog();
        $event_log->debug('foo.bar4', ['time' => '123xxx',]);
        $json_data = $this->readJsonFileData($el_params);
        PHPUnit::assertEquals('foo.bar4', $json_data['name']);
        PHPUnit::assertEquals(123, $json_data['event']['time']);

    }



    // ------------------------------------------------------------------------

    protected function initLog() {
        $tmp_filename = tempnam(sys_get_temp_dir(), 'eventlog');
        $monolog_logger = new Monolog\Logger('testing');
        $test_handler = new Monolog\Handler\TestHandler();
        $monolog_logger->pushHandler($test_handler);
        $event_log = new EventLog(new Writer($monolog_logger), $tmp_filename);
        return [
            $event_log,
            [
                'handler'      => $test_handler,
                'tmp_filename' => $tmp_filename,
            ]
        ];
    }

    protected function readJsonFileData($el_params) {
        $fd = fopen($el_params['tmp_filename'], 'r');
        rewind($fd);
        $contents = fread($fd, filesize($el_params['tmp_filename']));
        fclose($fd);
        $json_data = json_decode($contents, true);
        if ($json_data === null) {
            throw new Exception("Failed to decode contents: $contents", 1);
        }
        return $json_data;
    }
}
