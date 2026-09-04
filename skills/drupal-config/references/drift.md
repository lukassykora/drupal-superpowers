# Config drift and import

## Diagnose (read-only)
```bash
drush config:status                 # what differs, and in which direction
drush cim --diff --no               # the full diff an import would apply, then abort at the prompt (Drush 12+)
drush config:get system.site        # actual active value
drush config:status --state=Only\ in\ sync\ dir   # Drush 12/13 filters
```
Through the adapter (`ddev drush …`). With MCP Tools: `get_config_status`, `get_config_diff`, `get_config_drift`.

## Interpret each item
| Direction | Likely meaning | Action |
|---|---|---|
| Only in sync dir | repo has config the site lacks (new module config not imported; module missing on site) | import (after checking the module is enabled) |
| Only in DB (active) | site has config the repo lacks (module enabled locally; editor created something) | export if intended, uninstall/delete if not |
| Different | values changed on one side | read the diff; editor change on prod → export first; code/deploy change → import |
| Same config, UUID differs | site reinstalled or config re-created | align UUIDs deliberately (`config:set … uuid` on LOCAL) or re-export; never `--partial` blindly |
| Import fails: dependency | config references a missing module/config | enable the module or fix `dependencies:` |
| Import fails: schema | invalid values for the schema (10.2+ validation) | fix the YAML or the schema |
| Split-managed | config belongs to an inactive split | check `config_split.config_split.<name>` status and environment |

## Rules
- Never `drush cim -y` on an environment you have not classified LOCAL/DISPOSABLE without the user's explicit approval and a reviewed diff; the guard blocks it.
- Never `--partial` or `--source` to force a subset unless the project's deploy uses them.
- Export before import when production editors may have changed config; the deploy process should encode this.
- Overrides in settings.php do not show in `config:status` and are not exported: check `settings*.php` when the UI value differs from the YAML.
- Config Split: `drush config-split:export <name>` / `import`; environment activation via `$config['config_split.config_split.<name>']['status']` in settings.

## Report
```
VERIFY L2 config:status PASS "ddev drush config:status" no differences
VERIFY L2 cim --diff --no  NOT VERIFIED adapter=none
```
Or list the drift items with the decision per item.
