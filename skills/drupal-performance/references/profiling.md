# Measuring and profiling

Run through the adapter (`ddev exec`, `lando ssh -c`, `docker compose exec <svc>`). Measure warm and cold cache separately; note the environment class. Never load-test a non-local environment.

## Quick numbers
```bash
# Response time and cache state
curl -s -o /dev/null -w 'HTTP %{http_code} total %{time_total}s\n' <site_url>/partners
curl -sI <site_url>/partners | grep -iE 'x-drupal-(dynamic-)?cache|cache-control'   # tags/contexts headers need http.response.debug_cacheability_headers: true in services.yml
# Query count + time for a route (LOCAL; read-only)
drush php:eval '
  \Drupal\Core\Database\Database::startLog("perf");
  $t = microtime(TRUE); $m = memory_get_usage();
  $r = \Drupal::service('http_kernel')->handle(\Symfony\Component\HttpFoundation\Request::create("/partners"));
  printf("status %d, %.3fs, %.1f MB, %d queries\n", $r->getStatusCode(), microtime(TRUE)-$t, (memory_get_peak_usage()-$m)/1048576, count(\Drupal\Core\Database\Database::getLog("perf")));
'
# Slowest queries from the log
drush php:eval '... usort($log, fn($a,$b)=>$b["time"]<=>$a["time"]); foreach (array_slice($log,0,10) as $q) printf("%.4fs %s\n", $q["time"], substr($q["query"],0,120));'
```
Kernel-level measurement: a Kernel test with `Database::startLog()` around the code under test gives a reproducible query count (assert it does not grow with N rows).

## Tools when the project has them
| Tool | Detect | Use |
|---|---|---|
| Webprofiler (`drupal/webprofiler`) | in lock | toolbar panels: database (queries with time), cache (hits/misses per bin), render (cacheability), events, services |
| Devel | in lock | `dpq()` for query strings, `devel_generate` for realistic data volume on LOCAL/DISPOSABLE |
| Xdebug profiler | `php -m | grep xdebug`, `ddev xdebug` | `xdebug.mode=profile`, cachegrind output → QCacheGrind/`webgrind`; CLI: `XDEBUG_MODE=profile drush ...` |
| XHProf / Tideways | in lock or `php -m` | function-level timings with less overhead |
| MySQL `EXPLAIN` | `drush sql:query "EXPLAIN SELECT ..."` (read-only) | index usage, filesort, temporary tables |
| Load testing | `ab`, `hey`, `wrk`, `k6` on LOCAL only | throughput and p95 before/after |
| Browser | Lighthouse / DevTools performance panel | front-end: assets, render blocking, image sizes |

## Report format
```
Before: /partners 1.84s, 312 queries, 46 MB (cold), 0.91s (warm, DPC MISS)
Change: loadMultiple + referencedEntities, one COUNT, tag-based cache
After:  /partners 0.21s, 9 queries, 18 MB (cold), 0.03s (warm, DPC HIT)
Method: drush php:eval kernel handle + Database::startLog on LOCAL (DDEV), 3 runs each, median
```
