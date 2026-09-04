---
name: drupal-setup-mcp
description: Use when asked to connect Claude Code to a Drupal site through MCP, to configure MCP Tools, MCP Server, or drush-mcp, or to create a project .mcp.json for Drupal introspection.
user-invocable: true
---

# Drupal MCP setup

**Core principle:** MCP is an optional, read-first introspection channel for a specific site. The config is project-scoped, uses environment variables for anything sensitive, defaults to read scope, and is never written for a non-local site without the user choosing it.

## Procedure

1. **Which server is available?** From the profile (`mcp.fingerprint_hints`, `modules.contrib`) and by asking: MCP Tools (`drupal/mcp_tools`, broadest read-only surface, scopes), MCP Server 2.x (`drupal/mcp_server` + `mcp_server_tool_bridge`, tools depend on Tool API/Tool Belt), drush-mcp (`@bloomidea/drush-mcp`, no Drupal module needed, shell-equivalent power), `drupal/mcp` 1.x (legacy, merging into mcp_server). Comparison in `drupal-runtime-verification/references/mcp-capabilities.md`.
2. **Confirm the module is installed** on that site (`composer show drupal/mcp_tools`, `drush pm:list --filter=mcp` through the adapter) before writing config; offer the `composer require` + `drush pm:enable` steps on LOCAL/DISPOSABLE only.
3. **Pick the template** from [references/templates/](references/templates/) and write `<project>/.mcp.json` (project scope). Ask before overwriting an existing file; merge instead when other servers exist.
   - `mcp-tools.json`: `drush mcp-tools:serve --server=development --uid=${DRUPAL_MCP_UID} --quiet` with `MCP_SCOPE=read`; DDEV variant uses `ddev drush`.
   - `mcp-server.json`: `drush mcp:server` (STDIO) or `{"type":"http","url":"${DRUPAL_URL}/mcp"}` with OAuth via `mcp_server_oauth`.
   - `drush-mcp.json`: `npx -y @bloomidea/drush-mcp --local --command "${DRUSH_CMD}"`; explain that it has no read-only mode.
4. **Explain the security choices** in two lines: which user the server runs as (`--uid=1` only on throwaway sites; otherwise a dedicated `mcp_executor` user with read permissions), the scope, and that HTTP variants need an API key/OAuth stored in the environment (`${DRUPAL_MCP_API_KEY}`), never in the file.
5. Tell the user to restart Claude Code / run `/mcp`, then verify with one read call (`get_site_status` or equivalent) and report what the site returned.

## Rules

- Never write credentials, tokens, or absolute hostnames of non-local sites into `.mcp.json`; use `${VAR}` placeholders and say which variables to export.
- Never enable write scopes, the Drush plugin of `drupal/mcp`, or `drupal_php_eval`-class tools on a site that is not LOCAL/DISPOSABLE.
- Do not add a plugin-level MCP config; this is per project.
- The repository stays the source of truth for code; MCP is for introspection and verification.
