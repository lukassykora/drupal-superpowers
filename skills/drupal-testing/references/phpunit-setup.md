# PHPUnit setup in a Drupal project

## Config file (first that exists wins)
1. Project `phpunit.xml` / `phpunit.xml.dist` at the Composer root (often extends core's).
2. `<docroot>/core/phpunit.xml.dist` with `-c <docroot>/core` and the test path as argument.
3. A `composer test` script or Makefile target that wraps the above (use it).

Requires `drupal/core-dev` (profile field `packages.core_dev`); without it, `phpunit`, Mink, and the test base classes are absent — report NOT VERIFIED and suggest `composer require --dev drupal/core-dev:^<major.minor>` rather than installing it yourself on a shared project.

## Environment variables
| Variable | Needed by | Example |
|---|---|---|
| `SIMPLETEST_DB` | Kernel, Functional | `mysql://db:db@db/db` (DDEV default), `sqlite://localhost/sites/default/files/.ht.sqlite` (Kernel-only quick runs) |
| `SIMPLETEST_BASE_URL` | Functional, FunctionalJavascript | `https://<project>.ddev.site` or `http://localhost` (must be reachable from PHP) |
| `BROWSERTEST_OUTPUT_DIRECTORY` | Functional | `<docroot>/sites/simpletest/browser_output` (writable) |
| `MINK_DRIVER_ARGS_WEBDRIVER` | FunctionalJavascript | chromedriver/selenium JSON; usually CI only |

DDEV projects often define these in `.ddev/config.yaml` under `web_environment`, or in `.ddev/docker-compose.*.yaml`; read them rather than inventing values. Lando: `.lando.yml` services `overrides.environment`.

## Commands (prefix via drupal-runtime)
```bash
vendor/bin/phpunit -c web/core web/modules/custom/saved_items/tests/src/Kernel        # one directory
vendor/bin/phpunit -c web/core --filter testGetSavedNodeIds web/modules/custom/saved_items/tests/src/Kernel/SavedItemsRepositoryTest.php
vendor/bin/phpunit -c web/core --group saved_items
vendor/bin/phpunit -c web/core --testsuite unit                                        # core suites: unit, kernel, functional, functional-javascript
```
Add `--debug` for progress on slow Functional runs; `--stop-on-failure` when iterating.

## Reading failures
- `Drupal\Core\Database\...` connection errors → `SIMPLETEST_DB` wrong or DB unreachable from PHP.
- `ServiceNotFoundException` in a Kernel test → module not in `$modules`, or a real container bug.
- `Table ... doesn't exist` → missing `installSchema()`/`installEntitySchema()`.
- `Missing config schema` → add `config/schema`; do not set `$strictConfigSchema = FALSE` to hide it.
- Functional 403 where 200 expected → the created test user lacks the permission (anonymous-403 trap), or the route requirement is wrong.
- Deprecation errors failing the run → the project's `SYMFONY_DEPRECATIONS_HELPER`; report, do not silence.

## PHPUnit version differences
| PHPUnit | Drupal | Style |
|---|---|---|
| 9 | 9.x, 10.0–10.1 | annotations (`@group`, `@covers`), `setUp(): void` |
| 10 | 10.2+ | attributes available; annotations still work |
| 11 | 11.x | attributes preferred (`#[Group('x')]`), data providers static |
Check `packages.phpunit` in the profile and copy the style of existing tests in the project.
