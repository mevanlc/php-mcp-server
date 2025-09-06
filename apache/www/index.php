<?php

use PhpMcp\Server\Context;
use PhpMcp\Server\Session\ArraySessionHandler;
use PhpMcp\Server\Session\Session;
use PhpMcp\Server\Utils\DocBlockParser;
use PhpMcp\Server\Utils\SchemaGenerator;

require __DIR__ . '/bootstrap.php';

$server = mcp_server();
$registry = $server->getRegistry();
$schemaGen = new SchemaGenerator(new DocBlockParser());

$raw = file_get_contents('php://input');
$decoded = json_decode($raw, true);

if ($decoded === null) {
    http_response_code(400);
    echo json_encode([
        'jsonrpc' => '2.0',
        'id' => null,
        'error' => ['code' => -32700, 'message' => 'Parse error'],
    ]);
    return;
}

$requests = is_array($decoded) && array_is_list($decoded) ? $decoded : [$decoded];
$responses = [];

foreach ($requests as $req) {
    $responses[] = handle_request($req, $registry, $server, $schemaGen);
}

header('Content-Type: application/json');
echo json_encode(array_is_list($decoded) ? $responses : $responses[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

function handle_request(array $req, $registry, $server, $schemaGen): array
{
    $id = $req['id'] ?? null;
    $method = $req['method'] ?? '';

    switch ($method) {
        case 'tools/list':
            $tools = [];
            foreach ($registry->getTools() as $name => $toolSchema) {
                $registered = $registry->getTool($name);
                $handler = $registered->handler;

                if (is_array($handler)) {
                    [$class, $method] = $handler;
                    $reflection = new ReflectionMethod($class, $method);
                } elseif (is_string($handler) && function_exists($handler)) {
                    $reflection = new ReflectionFunction($handler);
                } else {
                    $reflection = new ReflectionMethod($handler, '__invoke');
                }

                $tools[] = [
                    'name' => $toolSchema->name,
                    'description' => $toolSchema->description,
                    'parameters' => $schemaGen->generate($reflection),
                ];
            }

            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => ['tools' => $tools],
            ];

        case 'tools/call':
            $params = $req['params'] ?? [];
            $toolName = $params['name'] ?? '';
            $args = $params['arguments'] ?? [];

            $registered = $registry->getTool($toolName);
            if ($registered === null) {
                return [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'error' => ['code' => -32601, 'message' => 'Tool not found'],
                ];
            }

            $session = new Session(new ArraySessionHandler());
            $context = new Context($session);

            try {
                $content = $registered->call($server->getConfiguration()->container, $args, $context);
                $result = ['content' => array_map(fn($c) => $c->toArray(), $content)];

                return [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'result' => $result,
                ];
            } catch (Throwable $e) {
                return [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'error' => ['code' => -32000, 'message' => $e->getMessage()],
                ];
            }

        default:
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => ['code' => -32601, 'message' => 'Method not found'],
            ];
    }
}
