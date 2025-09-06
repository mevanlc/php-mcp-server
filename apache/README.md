# Apache MCP Example

This folder contains a minimal synchronous example for using the
`php-mcp/server` components in a traditional Apache + PHP environment.

A single `index.php` endpoint accepts JSON-RPC 2.0 requests to both list
available tools and invoke them. The tools themselves (`Context7Tools`)
proxy to the public [Context7](https://context7.com) API and are discovered
via attributes.

## Example requests

List tools:

```bash
curl -X POST http://localhost/apache/index.php \
  -H 'Content-Type: application/json' \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
```

Call search tool:

```bash
curl -X POST http://localhost/apache/index.php \
  -H 'Content-Type: application/json' \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"search","arguments":{"query":"react"}}}'
```

These scripts intentionally avoid any event loop or SSE transports and are
intended for "plain" request/response PHP setups.
