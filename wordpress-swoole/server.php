<?php

use Swoole\Constant;
use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Coroutine\FastCGI\Proxy;

// WordPress project root directory on the server
$documentRoot = '/var/www/html';

/*
 * The port here needs to be consistent with the WordPress configuration. Generally, the port is not      * specified, but usually is port 80.
 */
$server = new Swoole\Http\Server('127.0.0.1', 8000, SWOOLE_BASE);

$server->set([
    Constant::OPTION_WORKER_NUM => swoole_cpu_num() * 2,
    Constant::OPTION_HTTP_PARSE_COOKIE => false,
    Constant::OPTION_HTTP_PARSE_POST => false,
    Constant::OPTION_DOCUMENT_ROOT => $documentRoot,
    Constant::OPTION_ENABLE_STATIC_HANDLER => true,

    // WordPress static resource paths
    Constant::OPTION_STATIC_HANDLER_LOCATIONS => ['/wp-admin', '/wp-content', '/wp-includes'],
]);

// Create a new proxy object used to transfer request from Swoole to the FastCGI FPM server
$fpmProxy = new Swoole\Coroutine\FastCGI\Proxy('127.0.0.1:9501', $documentRoot);

// Every HTTP request is handled by Swoole but passed over to PHP-FPM to execute
$server->on('Request', function(Swoole\Http\Request $request, Swoole\Http\Response $response) use ($fpmProxy)
{
    // Actual proxy transfer from Swoole HTTP Server to the PHP FPM Server
    $fpmProxy->pass($request, $response);
});

$server->start();