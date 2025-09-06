<?php

use PhpMcp\Server\Server;

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Context7Tools.php';

/**
 * Build the server and discover attribute-based tools.
 *
 * This example keeps things simple and re-runs discovery on each request.
 * For production use, cache the registry between requests.
 */
function mcp_server(): Server
{
    static $server = null;
    if ($server !== null) {
        return $server;
    }

    $server = Server::make()
        ->withServerInfo('Context7 Proxy', '1.0')
        ->build();
    $server->discover(__DIR__);

    return $server;
}
