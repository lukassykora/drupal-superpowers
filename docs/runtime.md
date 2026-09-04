# Runtime adapter

`scripts/drupal-runtime` answers two questions for every command Claude wants to run: *how* to run it here, and *where* it would run. No skill hard-codes `drush cr`.

## Resolution order

1. **Project wrappers** are reported, not substituted: Composer scripts (`test`, `lint`, `phpcs`, `phpstan`, `analyse`, `check`, `ci`, `quality`) and Makefile targets. Skills prefer them.
2. **DDEV**: `.ddev/config.yaml` and the `ddev` binary → `ddev drush`, `ddev composer`, `ddev exec php`, `ddev exec vendor/bin/<tool>`, `ddev npm`. State from `ddev describe -j` unless `--quick`.
3. **Lando**: `.lando.yml` and `lando` → `lando drush`, `lando composer`, `lando ssh -c "vendor/bin/<tool>"`.
4. **Docker Compose**: the first of `docker-compose.yml|yaml`, `compose.yml|yaml` with a service named like `php|web|app|drupal|appserver|cli` (else the first service) and the `docker` binary → `docker compose [-p <name>] exec <svc> …`. The project name comes from `COMPOSE_PROJECT_NAME`, then the file's top-level `name:`, else Docker's default; without it, state detection reported a running lab as stopped (found in Stage 8).
5. **Native**: `vendor/` present → `vendor/bin/drush`, `vendor/bin/phpunit`, `composer`, `php`, `npm` from PATH.
6. **None**: nothing runnable; only host `php` (if any) for `php -l`.

A runtime the project *declares* (e.g. `.ddev/config.yaml`) whose binary is missing here is listed under `declared` with the evidence, and resolution continues; skills tell the user "this project uses DDEV, which is not installed on this machine" rather than silently using something else.

## Output

```json
{
  "adapter": "ddev", "declared": ["ddev"], "state": "running",
  "commands": {"drush": "ddev drush", "composer": "ddev composer", "php": "ddev exec php",
               "phpunit": "ddev exec vendor/bin/phpunit", "phpcs": "ddev exec vendor/bin/phpcs", "phpstan": "ddev exec vendor/bin/phpstan", "npm": "ddev npm"},
  "project_commands": ["composer test", "composer lint"],
  "site_url": "https://myproj.ddev.site",
  "environment": {"class": "LOCAL", "evidence": "local runtime/db host"},
  "browser": {"playwright": false},
  "disposable_lab": {"possible": true, "engine": "ddev"}
}
```
`--summary` prints the human form used in the session brief; `--quick` skips live probes (used by hooks).

## Environment classification

See [security.md](security.md) §1. Signals: the lab marker file, hosting environment variables, database hosts in `settings.php`, trusted host patterns, references to hosting variables in settings, and the adapter itself. Not provably local → `UNKNOWN`.

## Verification levels

| Level | What | Commands (through the adapter) |
|---|---|---|
| L1 static | syntax, coding standards, static analysis | `php -l`, `phpcs --standard=<project or Drupal,DrupalPractice>`, `phpstan analyse`, YAML/Twig lint, `composer validate/audit` |
| L2 Drupal automated | tests and bootstrap | `phpunit -c <config> <path>`, `drush status`, `drush cr`, `drush updb -n`, `drush config:status`, `drush cim --preview=diff` |
| L3 live | the running site | `curl -i`, `drush user:login` links + browser, `drush watchdog:show`, two-user cache checks |

Every check is recorded as `VERIFY <level> <check> <PASS|FAIL|NOT VERIFIED|NOT APPLICABLE> "<command>" <result>`; `drupal-verification` builds the completion report from these lines.

## DDEV policy

- A project that uses DDEV is driven through DDEV. Claude does not start a stopped project without saying so.
- No runtime at all: Claude may *offer* a disposable environment (DDEV if installed, otherwise Docker Compose from the plugin's recipe) under `${CLAUDE_PLUGIN_DATA}/labs/<name>` with a `.drupal-superpowers-lab` marker; it never installs Docker or DDEV, never changes host configuration, never touches the project's own environment. Teardown is one command. Details: `skills/drupal-runtime-verification/references/disposable-lab.md`.
- Docker Compose recipe for the lab: `fixtures/lab-compose/` — verified on 2026-09-04 with a real Drupal 10.6.16 install (PHP 8.3.33, MariaDB 10.11): the php service installs extensions and Composer on first start (~2 min), `drush site:install` and Kernel tests on MariaDB work; `composer require drupal/core-dev` needs `--with-all-dependencies` on a fresh recommended-project.

## Precedence between sources of truth

| Question | First | Then |
|---|---|---|
| What is the code / config supposed to be? | repository (files, `config/sync`) | — |
| What is the installed state? | `drush … --format=json` read commands | read-only Drupal MCP tools |
| What does a user see? | HTTP request / browser | logs |

One layer never replaces the others; a green `phpcs` says nothing about a 403.

## Known limitations

- No support yet for remote development environments (SSH-based) or hosting CLIs (`acli`, `terminus`, `platform`); commands for those must come from the project's own docs and are treated as non-local.
- `ddev describe` adds ~0.5 s; hooks use `--quick`.
- Multisite: the adapter resolves the default site; pass `--uri` explicitly in commands when the profile reports `multisite: true`.
