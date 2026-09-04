# Verification levels

Commands are shown in native form; prefix with the adapter (`ddev exec`, `lando ssh -c`, `docker compose exec <svc>`) as resolved by `drupal-runtime`. Project wrappers (`composer test`, `make lint`) take precedence.

## L1 static

| Check | Command | Pass criterion |
|---|---|---|
| syntax | `php -l <file>` per changed PHP-family file | no parse errors |
| coding standards | `vendor/bin/phpcs --standard=<phpcs.xml(.dist) or Drupal,DrupalPractice> <changed paths>` (`phpcbf` for fixable) | 0 errors; warnings reported |
| static analysis | `vendor/bin/phpstan analyse -c <phpstan.neon(.dist)> <changed paths>` (phpstan-drupal + deprecation rules if configured) | 0 errors at the project's level; never lower the level to pass |
| YAML / Twig | `php -r` YAML parse, `drush twig:lint` (Drush 12+) or the project's twig lint | parses |
| composer | `composer validate`, `composer audit` when dependencies changed | valid / no known advisories |
| JS/CSS | project ESLint / Stylelint config when present | |

## L2 Drupal automated

| Check | Command | Notes |
|---|---|---|
| unit / kernel tests | `vendor/bin/phpunit -c <docroot>/core <path to tests>` (or project `phpunit.xml`) | Kernel needs `SIMPLETEST_DB`; DDEV usually sets it via `.ddev/config.yaml` `web_environment` |
| functional tests | same, with `SIMPLETEST_BASE_URL` and `BROWSERTEST_OUTPUT_DIRECTORY` | needs a web server reachable from PHP |
| functional JS | same plus `MINK_DRIVER_ARGS_WEBDRIVER` (chromedriver/selenium) | often only in CI |
| bootstrap / container | `drush status --format=json`, `drush cr` | container compiles; ServiceNotFound/plugin errors surface here |
| database updates | `drush updatedb:status`, `drush updb -n` (dry) | pending updates listed |
| config | `drush config:status`, `drush cim --preview=diff` (no `-y`) | drift is a finding, not something to auto-fix |
| module install | `drush pm:enable <module> -n` on a disposable/local site only | proves info.yml, schema, config/install |

## L3 live

| Check | Command / action | Evidence |
|---|---|---|
| HTTP | `curl -sSi <site_url>/path` (anonymous), with a session cookie or `drush user:login --uri=<site_url> <user>` link for authenticated | status code, headers (`X-Drupal-Cache`, `X-Drupal-Dynamic-Cache`, `Cache-Control`), body excerpt |
| access | request as anonymous, as a user without the permission, as a user with it | 403 / 403 / 200 (or 302 to login) |
| cache correctness | two different users request the same page; compare personalized parts; check `X-Drupal-Dynamic-Cache` HIT/MISS | no cross-user leakage |
| browser flows | login, form submit, AJAX, redirects, admin UI, JS behaviours | screenshots/DOM assertions, console errors, failed network requests |
| logs | `drush watchdog:show --count=30`, `--severity=Error`, web server / container logs | no new errors attributable to the change |
| cron / queue | `drush cron`, `drush queue:list`, `drush queue:run <name>` | items processed |

## Report line format

```
VERIFY <L1|L2|L3> <check> <PASS|FAIL|NOT VERIFIED|NOT APPLICABLE> "<command>" <one-line result or reason>
```
