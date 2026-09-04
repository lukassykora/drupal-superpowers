#!/usr/bin/env python3
"""Minimal stdio MCP server imitating Drupal MCP Tools (read scope). No dependencies."""
import json
import sys

SITE = {
    "site_name": "Fixture site",
    "drupal_version": "11.4.6",
    "php_version": "8.3.24",
    "database": "mysql 8.0",
    "modules": {"enabled": ["block", "node", "path_alias", "saved_items", "greeting_block", "system", "user"], "security_updates": []},
    "maintenance_mode": False,
    "cron_last": "2026-09-04T06:00:00Z",
}
CONTENT_TYPES = [
    {"id": "article", "label": "Article", "fields": ["body", "field_image", "field_tags"]},
    {"id": "page", "label": "Basic page", "fields": ["body"]},
]
FIELDS = {
    "article": [
        {"name": "body", "type": "text_with_summary", "required": False},
        {"name": "field_image", "type": "image", "required": False},
        {"name": "field_tags", "type": "entity_reference", "target": "taxonomy_term", "required": False},
    ],
    "page": [{"name": "body", "type": "text_with_summary", "required": False}],
}
CONFIG = {
    "system.site": {"name": "Fixture site", "mail": "admin@example.com", "page": {"front": "/node"}},
    "system.performance": {"cache": {"page": {"max_age": 3600}}, "css": {"preprocess": True}},
}
PERMISSIONS = {
    "anonymous": ["access content"],
    "authenticated": ["access content", "use saved items"],
    "editor": ["access content", "use saved items", "create article content", "edit own article content"],
}
WATCHDOG = [
    {"wid": 101, "type": "php", "severity": "error", "message": "ServiceNotFoundException: You have requested a non-existent service \"entity.manager\"", "timestamp": "2026-09-04T05:12:00Z"},
    {"wid": 102, "type": "cron", "severity": "notice", "message": "Cron run completed.", "timestamp": "2026-09-04T06:00:00Z"},
]

TOOLS = [
    {"name": "mcp_tools_list_available", "description": "List available MCP Tools categories and the active scope.", "inputSchema": {"type": "object", "properties": {}}},
    {"name": "get_site_status", "description": "Site status: Drupal/PHP versions, enabled modules, security updates, cron, maintenance mode.", "inputSchema": {"type": "object", "properties": {}}},
    {"name": "list_content_types", "description": "List content types (bundles of node).", "inputSchema": {"type": "object", "properties": {}}},
    {"name": "get_content_type_fields", "description": "Field definitions for a content type.", "inputSchema": {"type": "object", "properties": {"content_type": {"type": "string"}}, "required": ["content_type"]}},
    {"name": "get_config", "description": "Read a configuration object by name.", "inputSchema": {"type": "object", "properties": {"name": {"type": "string"}}, "required": ["name"]}},
    {"name": "get_permissions", "description": "Permissions per role.", "inputSchema": {"type": "object", "properties": {}}},
    {"name": "analyze_watchdog", "description": "Recent watchdog entries with severity.", "inputSchema": {"type": "object", "properties": {"severity": {"type": "string"}}}},
    {"name": "clear_all_caches", "description": "Clear all caches (requires write scope).", "inputSchema": {"type": "object", "properties": {"confirm": {"type": "boolean"}}}},
]

def call(name, args):
    if name == "mcp_tools_list_available":
        return {"scope": "read", "categories": ["core", "structure", "config", "users", "logs"], "write_enabled": False}
    if name == "get_site_status":
        return SITE
    if name == "list_content_types":
        return CONTENT_TYPES
    if name == "get_content_type_fields":
        ct = args.get("content_type")
        if ct not in FIELDS:
            raise ValueError(f"Unknown content type: {ct}")
        return FIELDS[ct]
    if name == "get_config":
        n = args.get("name")
        if n not in CONFIG:
            raise ValueError(f"Config object not found: {n}")
        return CONFIG[n]
    if name == "get_permissions":
        return PERMISSIONS
    if name == "analyze_watchdog":
        sev = args.get("severity")
        return [e for e in WATCHDOG if not sev or e["severity"] == sev]
    if name == "clear_all_caches":
        raise PermissionError("Scope 'write' required; this connection has scope 'read'.")
    raise ValueError(f"Unknown tool: {name}")

def respond(msg_id, result=None, error=None):
    out = {"jsonrpc": "2.0", "id": msg_id}
    if error is not None:
        out["error"] = error
    else:
        out["result"] = result
    sys.stdout.write(json.dumps(out) + "\n")
    sys.stdout.flush()

def main():
    for line in sys.stdin:
        line = line.strip()
        if not line:
            continue
        try:
            msg = json.loads(line)
        except json.JSONDecodeError:
            continue
        method = msg.get("method")
        msg_id = msg.get("id")
        if method == "initialize":
            respond(msg_id, {"protocolVersion": msg.get("params", {}).get("protocolVersion", "2024-11-05"),
                             "capabilities": {"tools": {}},
                             "serverInfo": {"name": "drupal-mcp-tools-stub", "version": "0.1.0"}})
        elif method == "notifications/initialized":
            continue
        elif method == "ping":
            respond(msg_id, {})
        elif method == "tools/list":
            respond(msg_id, {"tools": TOOLS})
        elif method == "tools/call":
            params = msg.get("params", {})
            try:
                result = call(params.get("name"), params.get("arguments") or {})
                respond(msg_id, {"content": [{"type": "text", "text": json.dumps(result, indent=2)}], "isError": False})
            except Exception as e:  # noqa: BLE001
                respond(msg_id, {"content": [{"type": "text", "text": f"Error: {e}"}], "isError": True})
        elif msg_id is not None:
            respond(msg_id, error={"code": -32601, "message": f"Method not found: {method}"})

if __name__ == "__main__":
    main()
