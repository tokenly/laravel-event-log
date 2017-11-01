An event logger for Laravel.  Used by various Tokenly services.

Provides `api.logApiCalls` middleware.

## Installation
- Install with `composer require tokenly/laravel-event-log` 

## Usage
```php
use EventLog;

// log events
EventLog::debug('user.loggedIn', ['userId' => $user_id]);

// log exceptions
try {
    // do something here
} catch (Exception $exception) {
    EventLog::logError('action.failed', $e);
}

```
