Custom connectors
Custom connectors are also available for (a) ChatGPT Pro users and (b) ChatGPT Business, Enterprise, and Edu workspaces. With this feature, you can add custom connectors that follow the Model Context Protocol (MCP) to connect to custom third-party apps and your internal sources.

Note: In Business, Enterprise, and Edu workspaces, only workspace owners, admins, and users with the respective setting enabled (for Enterprise/Edu) can add custom connectors. Users with a regular member role do not have the ability to add custom connectors themselves.

Once a connector is added and enabled by an owner or admin user, it becomes available for all members of the workspace to use.

As with other connectors, end users must authenticate with each connector themselves before first use. For custom connectors configured with no authentication, end users must still individually search and connect to the connector within their settings before first use.

Please note that custom connectors are not verified by OpenAI and are intended for developer use only. You should only add custom connectors to your workspace if you know and trust the underlying application. Learn more.

For additional information regarding how to setup a custom connector using MCP, please refer to our documentation here: http://platform.openai.com/docs/mcp

# http://platform.openai.com/docs/mcp



Build an MCP server to use with ChatGPT connectors, deep research, or API integrations.

[Model Context Protocol](https://modelcontextprotocol.io/introduction) (MCP) is an open protocol that's becoming the industry standard for extending AI models with additional tools and knowledge. Remote MCP servers can be used to connect models over the Internet to new data sources and capabilities.

In this guide, we'll cover how to build a remote MCP server that reads data from a private data source (a [vector store](https://platform.openai.com/docs/guides/retrieval)) and makes it available in ChatGPT via connectors in chat and deep research, as well as [via API](https://platform.openai.com/docs/guides/deep-research).

## Configure a data source

You can use data from any source to power a remote MCP server, but for simplicity, we will use [vector stores](https://platform.openai.com/docs/guides/retrieval) in the OpenAI API. Begin by uploading a PDF document to a new vector store - [you can use this public domain 19th century book about cats](https://cdn.openai.com/API/docs/cats.pdf) for an example.

You can upload files and create a vector store [in the dashboard here](https://platform.openai.com/storage/vector_stores), or you can create vector stores and upload files via API. [Follow the vector store guide](https://platform.openai.com/docs/guides/retrieval) to set up a vector store and upload a file to it.

Make a note of the vector store's unique ID to use in the example to follow.

![vector store configuration](https://cdn.openai.com/API/docs/images/vector_store.png)

## Create an MCP server
Create an MCP server

Next, let's create a remote MCP server that will do search queries against our vector store, and be able to return document content for files with a given ID.

In this example, we are going to build our MCP server using Python and FastMCP. A full implementation of the server will be provided at the end of this section, along with instructions for running it on Replit.

Note that there are a number of other MCP server frameworks you can use in a variety of programming languages. Whichever framework you use though, the tool definitions in your server will need to conform to the shape described here.

To work with ChatGPT Connectors or deep research (in ChatGPT or via API), your MCP server must implement two tools - search and fetch.
search tool

The search tool is responsible for returning a list of relevant search results from your MCP server's data source, given a user's query.

Arguments:

A single query string.

Returns:

An object with a single key, results, whose value is an array of result objects. Each result object should include:

    id - a unique ID for the document or search result item
    title - human-readable title.
    url - canonical URL for citation.

In MCP, tool results must be returned as a content array containing one or more "content items." Each content item has a type (such as text, image, or resource) and a payload.

For the search tool, you should return exactly one content item with:

    type: "text"
    text: a JSON-encoded string matching the results array schema above.

The final tool response should look like:

{
  "content": [
    {
      "type": "text",
      "text": "{\"results\":[{\"id\":\"doc-1\",\"title\":\"...\",\"url\":\"...\"}]}"
    }
  ]
}

fetch tool

The fetch tool is used to retrieve the full contents of a search result document or item.

Arguments:

A string which is a unique identifier for the search document.

Returns:

A single object with the following properties:

    id - a unique ID for the document or search result item
    title - a string title for the search result item
    text - The full text of the document or item
    url - a URL to the document or search result item. Useful for citing specific resources in research.
    metadata - an optional key/value pairing of data about the result

In MCP, tool results must be returned as a content array containing one or more "content items." Each content item has a type (such as text, image, or resource) and a payload.

In this case, the fetch tool must return exactly one content item with
type: "text"
. The text field should be a JSON-encoded string of the document object following the schema above.

The final tool response should look like:

{
  "content": [
    {
      "type": "text",
      "text": "{\"id\":\"doc-1\",\"title\":\"...\",\"text\":\"full text...\",\"url\":\"https://example.com/doc\",\"metadata\":{\"source\":\"vector_store\"}}"
    }
  ]
}

