# Drupal MCP capability detection

Claude Code names MCP tools `mcp__<server>__<tool>`; `<server>` is the user's arbitrary key, so detect by **tool name**, then confirm with one cheap read call. Multiple servers may coexist.

## Fingerprints

| Implementation | Fingerprint tool names | Cheap probe | Read-only mode |
|---|---|---|---|
| MCP Tools (`drupal/mcp_tools`) | `mcp_tools_list_available`, `get_site_status`, `list_content_types`, `get_config_status` | `mcp_tools_list_available` (shows scope + categories) | scopes read/write/admin; new installs default to read |
| MCP Server 2.x + tool bridge | names starting `tool_api__` | any `tool_api__*` whose Tool API id is a read tool (list entity types, system status, `project_context_snapshot`) | per-tool access + OAuth scopes; `destructiveHint` set |
| `drupal/mcp` 1.x | `info`, `status`, `search-content`, `jsonapi_read`, `jsonapi_schema`, names containing `___` | `info` | per-plugin/tool enable; Drush plugin whitelisted |
| drush-mcp (Bloomidea) | `drupal_status`, `drupal_introspect`, `drupal_field_info`, `drupal_drush`, `drupal_php_eval` | `drupal_status` | **none** — shell-equivalent |
| drupal-mcp-connector | `drupal_mcp_whoami`, `drupal_governance_status`, `drupal_report_*` | `drupal_mcp_whoami` | presets (production-strict default) |
| miniOrange | `site_info`, `module_status`, `content_search` | `site_info` | built-ins read-only |
| DDEV MCP | `ddev_exec`, `ddev_describe`, `ddev_db_query` | `ddev_describe` | `ddev_exec` is shell-equivalent |

Filesystem hints (from `drupal-profile`): `.mcp.json` commands containing `mcp-tools:serve`, `mcp:server`, `drush-mcp`, `mcp-server-drupal`, `ddev-mcp`; `composer.lock` with `drupal/mcp_tools`, `drupal/mcp_server`, `drupal/mcp`, `drupal/project_context_connector`.

## Capability map (fill from what is present; fall back rightwards)

| Capability | MCP Tools | mcp_server bridge | mcp 1.x | drush-mcp | Bash fallback |
|---|---|---|---|---|---|
| site status / versions | `get_site_status`, `get_system_status` | `tool_api__*system_status*` | `status`, `info` | `drupal_status` | `drush status --format=json` |
| modules + security updates | `get_site_status`, `check_security_updates` | `tool_api__project_context_snapshot` | `status` | `drupal_drush pm:list` | `drush pm:list --format=json`, `drush pm:security` |
| content types / fields | `list_content_types`, `get_content_type_fields`, `get_field_types` | Tool Belt list/definitions | `jsonapi_schema` | `drupal_introspect`, `drupal_field_info` | `drush field:info`, config YAML |
| config get / diff / drift | `get_config`, `list_config`, `get_config_status`, `get_config_diff`, `get_config_drift` | — | — | `drupal_config_get` | `drush config:get`, `drush config:status` |
| roles / permissions | `get_roles`, `get_permissions`, `get_role_permissions` | — | — | `drupal_drush role:list` | `drush role:list --format=json` |
| logs | `analyze_watchdog` | — | — | `drupal_watchdog` | `drush watchdog:show --format=json` |
| queues / cron | `get_queue_status`, `check_cron_status` | — | — | `drupal_drush queue:list` | `drush queue:list` |
| routes | — | — | — | — | `drush route`, routing YAML |
| cache clear (write) | `clear_all_caches` | bridged | Drush tool | `drupal_cache_rebuild` | `drush cr` |
| entity CRUD (write) | content/structure submodules | Tool Belt | Tool API / agents | `drupal_entity_*` | `drush php:script` |

## Classification for the guard

- **Read-only, call freely**: everything in the rows above without "(write)"; `analyze_*`, `get_*`, `list_*`, `search-content`, `drupal_mcp_whoami`, resources `drupal://site/*`.
- **Mutating but routine (announce once)**: cache clears, `publish_content`, `export_config`.
- **Destructive (explicit approval per call, never on non-local hosts)**: `create_*/update_*/delete_*` on structure, `grant_permission`/`revoke_permission`, `delete_content`, `drupal_entity_delete/update`, `drupal_config_set`, recipes apply, migrations, user changes, Drush wrappers for `config:import`, `updatedb`, `sql:*`, `pm:uninstall`, `site:install`.
- **Shell-equivalent (same rules as Bash, guard applies)**: `drupal_drush`, `drupal_php_eval`, `drupal_sql_query`, whitelisted Drush tools, miniOrange `drush_command`, `ddev_exec`, `ddev_db_query` in write mode.

Warn when the server runs as uid 1 (`--uid=1`, drush-mcp bridge `--user=1` default). Refuse writes when the configured server points at a host that is not `localhost`, `*.ddev.site`, `*.lndo.site`, or a Docker service name.
