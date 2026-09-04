# MCP stub server

A dependency-free Python MCP server (stdio, newline-delimited JSON-RPC) that imitates the read-only
surface of Drupal MCP Tools: `mcp_tools_list_available`, `get_site_status`, `list_content_types`,
`get_content_type_fields`, `get_config`, `get_permissions`, `analyze_watchdog`, plus one write tool
`clear_all_caches` that is rejected with a scope error (the stub runs in `read` scope).

Used by the `site-mcp` fixture via `.mcp.json` and by the mcp-present evals. Start manually:

    python3 fixtures/mcp-stub/server.py

It reads JSON-RPC requests from stdin and answers on stdout. Nothing touches a real Drupal site.
