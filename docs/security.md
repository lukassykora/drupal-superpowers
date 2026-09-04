# Security model

How the plugin keeps a Drupal project, its environments, and its secrets safe while Claude works, and how the plugin itself avoids becoming an attack surface.

## 1. Environment classification

`scripts/drupal-runtime` classifies where commands would run:

| Class | Evidence | Effect |
|---|---|---|
| `DISPOSABLE` | `.drupal-superpowers-lab` marker file written by the plugin's lab recipe | destructive commands allowed without prompting |
| `LOCAL` | DDEV/Lando/Compose adapter, or local database hosts and local trusted-host patterns only | routine state changes (cache rebuild, module enable, updb, cim after a reviewed diff) allowed; destructive list still blocked |
| `DEVELOPMENT` / `STAGING` / `PRODUCTION` | explicit hosting environment variables (`AH_SITE_ENVIRONMENT`, `PANTHEON_ENVIRONMENT`, `PLATFORM_BRANCH`, `LAGOON_ENVIRONMENT_TYPE`, `DRUPAL_ENV`, `ENVIRONMENT`, `APP_ENV`) | read-only by default; every state change announced; destructive list blocked |
| `UNKNOWN` | non-local database host, non-local trusted hosts, hosting variables referenced in settings, or no signals at all | treated like PRODUCTION for the guard |

Anything not provably local is `UNKNOWN`. The classification is recomputed per command by the guard (`--quick`, no live probes) so it cannot go stale within a session.

## 2. The destructive-command guard (PreToolUse on Bash)

Blocks with exit 2 and a reason, outside `DISPOSABLE`:

- `drush sql:drop`, `sql-drop`, `site:install` / `si`, `sql:sync`, `entity:delete`, `pm:uninstall`, `user:password`
- `drush cim -y` / `config:import -y` (the preview form `cim --preview=diff` is allowed)
- destructive SQL through `drush sql:query` / `sql:cli` (`DROP`, `DELETE`/`TRUNCATE` without a `WHERE`)
- `DROP TABLE/DATABASE`, `DELETE FROM x;`/`TRUNCATE` without restriction
- `rm -rf` on the project root, `/`, `~`, `.`, or `*`
- `git reset --hard`, `git clean -f`, `git push --force`
- unbounded `composer update`

Wrappers are normalised first, so `ddev drush …`, `lando drush …`, `docker compose exec php vendor/bin/drush …` and `vendor/bin/drush …` all match. The list lives in `hooks/scripts/guard-bash` and is exercised by `evals/scenarios/dangerous-env`.

The guard cannot see conversational approval. When the user has explicitly approved a blocked command for that environment, Claude says so and asks the user to run it with `! <command>`, or takes a backup first (`drush sql:dump`) and proposes a disposable copy.

Limits: the guard inspects the command string; obfuscated or multi-step destructive actions (a script file that drops tables) are not caught. Skills instruct Claude to announce every state-changing command on non-local environments regardless of the guard.

The Stop hook (`stop-gate`) never blocks destructively: it asks once for a verification report when Drupal files changed without one, and exits silently when `stop_hook_active` is set.

## 3. Agents and least privilege

- `drupal-researcher`, `drupal-security-reviewer`, `drupal-code-reviewer` have no Edit/Write tools; the researcher and reviewer with Bash are instructed to use read-only commands, and the guard applies inside agents.
- `drupal-test-engineer` runs tests and linters through the resolved runtime; it never modifies tests or code.
- `drupal-upgrade-specialist` may edit and run Composer with named packages; unbounded updates and destructive Drush remain blocked.
- Independent review: reviewers receive the diff as a file and `[GLOBAL_CONSTRAINTS]`, never the implementer's narrative as evidence.

## 4. Drupal MCP

- Optional; nothing in the plugin requires it; no plugin-level `.mcp.json` is shipped (it would apply to every project).
- Detection by tool-name fingerprints; read-only tools may be used freely for introspection; mutating tools follow the same approval rules as the equivalent Drush command; `drupal_drush`, `drupal_php_eval`, `drupal_sql_query`, `ddev_exec` are shell-equivalent and fall under the guard's policy.
- Writes through MCP are refused when the server points at a host that is not `localhost`, `*.ddev.site`, `*.lndo.site`, or a Docker service name.
- `drupal-setup-mcp` writes project-scoped config with `${VAR}` placeholders for keys and URLs; recommends `MCP_SCOPE=read` and a dedicated read-only user instead of uid 1 for anything but throwaway sites. Production MCP endpoints are treated as sensitive.

## 5. Secrets and data

- Scripts read `settings*.php` only to extract database host names and trusted host patterns; they never print credentials, and the session brief contains none.
- Skills forbid logging request bodies, printing tokens in findings, or storing secrets in config, state, `.mcp.json`, or code; environment variables and the project's secret mechanism are the only allowed locations.
- Eval fixtures contain placeholder credentials only (`db`/`db`, `fixture-not-a-secret`).

## 6. Git and repository

- No `git commit`, `push`, `reset`, `clean`, `rebase`, or branch changes unless the user asked for exactly that; the guard blocks the destructive forms.
- The repository is the source of truth for code; MCP/Drush are for introspection and verification.

## 7. Supply chain of the plugin itself

- Zero runtime dependencies; bash scripts use `php` or `python3` only for JSON parsing; no network calls except the explicit, optional lookups (`drupal-lookup`, `contrib-info`) to drupal.org and Drupal Code Query, which are read-only GETs with timeouts and no credentials.
- No telemetry. No code is copied from GPL or unlicensed sources ([ATTRIBUTION.md](../ATTRIBUTION.md)).
- CI: `claude plugin validate --strict`, shellcheck when available, `bash -n`, `php -l` on fixtures.

## 8. Known gaps

- The guard is string-based (see §2 limits).
- `drupal-runtime` cannot classify environments that expose no signals at all; they stay `UNKNOWN`, which is the conservative default.
- Browser verification tools available to Claude are outside this plugin's control; skills restrict authenticated browser flows to LOCAL/DISPOSABLE.
