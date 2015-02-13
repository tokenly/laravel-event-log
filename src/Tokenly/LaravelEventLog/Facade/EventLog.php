<?php

namespace Tokenly\LaravelEventLog\Facade;

use Illuminate\Support\Facades\Facade;

class EventLog extends Facade {


    protected static function getFacadeAccessor() { return 'eventlog'; }


}
