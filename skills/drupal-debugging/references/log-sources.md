# Log and evidence sources

Run through the adapter from `drupal-runtime` (prefix `ddev exec`, `lando ssh -c`, `docker compose exec <svc>`). Read-only unless marked.

| Source | Command / location | What it tells you |
|---|---|---|
| Watchdog (dblog) | `drush watchdog:show --count=30`, `--severity=Error`, `--type=php`, `--extended`, `--format=json` | PHP errors with file:line, access denied, page not found, custom logs |
| Syslog | `/var/log/syslog`, hosting log stream when `syslog` module replaces dblog | same as above on production-like hosts |
| PHP error log | DDEV: `ddev logs -s web`; Lando: `lando logs -s appserver`; native: `php -i \| grep error_log`, web server error log | fatals that never reached watchdog, memory/timeout |
| Web server access log | `ddev logs -s web`, nginx/apache access log | status codes, slow requests, the actual URL hit |
| Full exception on screen (LOCAL only) | `$config['system.logging']['error_level'] = 'verbose';` in `settings.local.php` (write; revert after) | stack trace in the browser |
| Drupal status | `drush status --format=json`, `drush core:requirements --severity=1` | bootstrap, DB, PHP version, warnings/errors on the status report |
| Container / routes | `drush cr` (rebuild; exception text names the cause), `drush route`, `drush php:eval` for read-only introspection | compile errors, route table |
| Config | `drush config:status`, `drush config:get <name>`, `drush cim --diff --no` | drift, actual values, what an import would change |
| Cache headers | `curl -sI <url>` → `X-Drupal-Cache`, `X-Drupal-Dynamic-Cache`, `Cache-Control`, `Vary` | whether the page came from page cache / dynamic page cache |
| Browser | console (JS errors), network (failed/slow requests, AJAX bodies), Elements (rendered markup) | front-end and AJAX failures |
| Tests | `phpunit --debug`, `BROWSERTEST_OUTPUT_DIRECTORY` HTML dumps | the page the functional test saw |
| Database (read-only) | `drush sql:query "SELECT ... LIMIT 20"` | actual stored values; never `DELETE`/`DROP` here |
| Xdebug / profiling | only if the project has it (`ddev xdebug status`); Webprofiler/Devel only if installed | step debugging; do not install on non-disposable environments |
| MCP (read-only) | `analyze_watchdog`, `get_site_status`, `drupal_watchdog` when present | same data without shell access |

Order of reading: watchdog → PHP log → the exception trace → config status → cache headers → browser. Quote the lines you relied on in the report.
