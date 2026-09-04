# Project Capability Profile fields

Output of `scripts/drupal-profile` (JSON) and `scripts/drupal-runtime` (JSON). `null`/`unknown` means "not determinable from files"; it never means "absent".

## drupal-profile

| Field | Source | How to use it |
|---|---|---|
| `project_kind` | composer.json type, lock, core/lib/Drupal.php, bootstrap.inc | site / custom-module / contrib-module / core selects the conventions |
| `drupal.version`, `.source` | composer.lock → Drupal.php → composer.json constraint | quote the source when stating the version |
| `drupal.distribution` | composer.json (`drupal/cms`), core.extension.yml profile | Drupal CMS uses recipes; a custom profile may own config |
| `router.class`, `.eol_date`, `.note` | `drupal-facts class` over references/versions/matrix.md | current / previous / eol / dev behaviour; a note means the matrix may be stale |
| `php.constraint`, `.local_cli` | composer.json / lock platform; `php -v` on the host | host PHP is not the project PHP when a container is used |
| `packages.*` | composer.lock | drush major, phpunit major, phpstan-drupal, coder, rector, upgrade_status, core-dev, composer-patches |
| `paths.custom_modules/custom_themes/profiles/recipes` | filesystem | where new code goes; never create a second custom directory |
| `paths.config_sync`, `.config_split` | settings.php, config/sync, lock | where exported config lives; split means environment-specific config exists |
| `modules.contrib/custom/patched` | lock, info files, composer.json extra.patches | patched packages must not be updated casually |
| `features.*` | core.extension.yml, lock, settings.php | multilingual → translations in design; moderation/workspaces → revisions and access; search stack; queue/cron providers; database driver |
| `quality.*` | phpcs.xml*, phpstan.neon*, phpunit.xml*, composer scripts, Makefile/Taskfile, CI, eslint/stylelint | the commands to run; project config beats plugin defaults |
| `frontend.*` | package.json, lockfiles, vite/webpack config, themes/*/components | build step needed before browser verification; SDC present |
| `testing_frameworks` | phpunit.xml*, lock (phpunit, DTT, drupal-extension), cypress/playwright/nightwatch configs | pick the cheapest available layer |
| `mcp.configured_servers`, `.fingerprint_hints` | .mcp.json, lock | which Drupal MCP server may be present; confirm by tool names in your tool list |
| `agent_skills.project/vendor` | .agents/skills, vendor/**/.agents/skills | project-provided skills to read first for their topics |
| `deployment.hints` | CI files, Makefile, composer scripts | the project's own deploy order (drush deploy / updb / cim / cr) |

## drupal-runtime

| Field | Meaning |
|---|---|
| `adapter` | ddev / lando / compose / native / none — how commands run |
| `declared` | runtimes the project declares even if their binary is missing here |
| `state` | running / stopped / unknown / n/a |
| `commands.*` | exact command prefix per tool, or null when unavailable |
| `project_commands` | composer scripts / make targets that wrap quality tools; prefer them |
| `site_url` | URL for HTTP/browser verification when known |
| `environment.class`, `.evidence` | DISPOSABLE / LOCAL / DEVELOPMENT / STAGING / UNKNOWN / PRODUCTION and why |
| `browser.playwright` | Playwright available for browser verification |
| `disposable_lab.engine` | ddev / docker / none — what a disposable environment could use |
