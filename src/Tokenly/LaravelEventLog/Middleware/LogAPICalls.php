<?php

namespace Tokenly\LaravelEventLog\Middleware;

use Closure;
use Tokenly\LaravelEventLog\Facade\EventLog;

class LogAPICalls {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // ------------------------------------------------------------------------
        // Log Before call

        $begin_log_vars = [
            'method'        => '',
            'route'         => '',
            'uri'           => '',
            'parameters'    => '',
            'inputBodySize' => '',
        ];

        // get the request details
        $method = $request->method();
        $begin_log_vars['method'] = $method;

        $route = $request->route();
        if ($route) {
            $route_uri = $route->getUri();
            $route_name = $route->getName();
            $route_params = $route->parameters();
        } else {
            $route_name = '[unknown]';
            $route_uri = '[unknown]';
            $route_params = [];
        }
        $begin_log_vars['route'] = $route_name;
        $begin_log_vars['uri'] = $route_uri;
        $begin_log_vars['parameters'] = $route_params;

        $body_size = $request->header('Content-Length');
        if (!$body_size) { $body_size = 0; }
        $begin_log_vars['inputBodySize'] = $body_size;
        EventLog::debug('apiCall.begin', $begin_log_vars);

        $response = $next($request);

        // ------------------------------------------------------------------------
        // Log after call

        $end_log_vars = [
            'method'     => $begin_log_vars['method'],
            'route'      => $begin_log_vars['route'],
            'uri'        => $begin_log_vars['uri'],
            'parameters' => $begin_log_vars['parameters'],
            'status'     => $response->getStatusCode(),
        ];

        if ($response->isServerError() OR $response->isClientError()) {
            EventLog::warning('apiCall.end', $end_log_vars);
        } else {
            EventLog::debug('apiCall.end', $end_log_vars);
        }


        return $response;
    }



}