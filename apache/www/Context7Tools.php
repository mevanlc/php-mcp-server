<?php

use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use PhpMcp\Server\Exception\McpServerException;

/**
 * Example tools that proxy Context7's library API.
 *
 * These tools are discovered via attributes and can be
 * invoked synchronously in a standard Apache/PHP setup.
 */
class Context7Tools
{
    private string $baseUrl = 'https://context7.com/api/v1';

    /**
     * Search Context7's catalog.
     */
    #[McpTool(name: 'search')]
    public function search(
        #[Schema(description: 'Search query')]
        string $query
    ): array {
        $url = $this->baseUrl . '/search?query=' . urlencode($query);
        return $this->fetchJson($url);
    }

    /**
     * Fetch documentation for a result id.
     */
    #[McpTool(name: 'fetch')]
    public function fetch(
        #[Schema(description: 'Result identifier')]
        string $id
    ): array {
        $url = $this->baseUrl . '/docs?ids=' . urlencode($id);
        return $this->fetchJson($url);
    }

    private function fetchJson(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);
        $data = @file_get_contents($url, false, $context);
        if ($data === false) {
            throw McpServerException::internalError('Context7 request failed');
        }

        $decoded = json_decode($data, true);
        if (! is_array($decoded)) {
            throw McpServerException::internalError('Invalid JSON from Context7');
        }

        return $decoded;
    }
}
