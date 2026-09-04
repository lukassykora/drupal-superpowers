---
name: drupal-performance
description: Use when a Drupal page, route, Views listing, cron, queue, or migration is slow or memory-heavy, when code loads entities in loops (N+1), runs queries per row, calls external APIs in requests, or when asked to profile, benchmark, or review Drupal performance and cache hit rates.
---

# Drupal performance

**Core principle:** measure, then change the thing the measurement points at, then measure again. In Drupal most slowness is one of five things: N+1 entity loads, per-row queries, missing or defeated caching (max-age 0, uncacheable contexts), expensive hooks/subscribers on every request, or external calls in the request path.

## When to use

Slow pages/routes/Views, high memory, timeouts in cron/queue/migration, performance review of a change, cache hit-rate questions. Not for correctness of cache metadata alone (`drupal-cacheability`), though the two overlap.

## Procedure

1. **Reproduce with a number**: response time and query count for the URL (`curl -w '%{time_total}'`, Webprofiler/Devel if installed, `drush php:eval` with timers), memory (`memory_get_peak_usage`), or the cron/queue wall time. Note cache state (cold vs warm) and the environment class.
2. **Locate** with [references/checklist.md](references/checklist.md): entity loads inside loops (`load()` per id instead of `loadMultiple()`), `getStorage()->load()` on referenced entities per row instead of `entity.entity_reference` batch loading, queries in loops, `count()` queries per row, Views without pagination, render arrays with `max-age 0`, personalized parts not placeholdered, `hook_entity_load`/`hook_preprocess` doing heavy work, event subscribers on every request, HTTP calls without cache/timeout, large `loadMultiple()` without limits, missing indexes on custom tables.
3. **Profile** when the checklist does not settle it ([references/profiling.md](references/profiling.md)): query log via `Database::startLog()` / Webprofiler, Xdebug/XHProf if the project has them, `drush watchdog` for slow-query logs, MySQL `EXPLAIN`, `ab`/`wrk`/`hey` for throughput on LOCAL only.
4. **Fix Drupal-natively**: `loadMultiple()` and pre-collected IDs, entity queries with `range()`, `Cache` API with tags for computed data, lazy builders for personalized fragments, Views caching (tag-based) and pagers, queue workers for slow side effects, `#cache` with proper metadata instead of `max-age 0`, `CacheableJsonResponse`, batch/cron for bulk work, database indexes via `hook_schema`/update hooks.
5. **Measure again**, same method, and report before/after with the command; note trade-offs (staleness, memory).
6. Keep scope: performance work does not restructure unrelated code; report other hotspots separately.

## Decision rules

- No number → no claim. "Should be faster" is `NOT VERIFIED`.
- N+1 fix first; it dominates almost every slow listing.
- Caching fixes need `drupal-cacheability` metadata; a cache without tags is a stale-data bug.
- External API in a request → cache with max-age/tags, timeouts, and a queue for writes; never synchronous without a bound.
- Don't add a cache bin, a custom table, or Redis because "it's faster"; prove the bottleneck first.
- Long profiling and log analysis go to the `drupal-performance-reviewer` agent.

## Works with process skills

`drupal-architecture` review row "performance"; `drupal-code-review` lens; `superpowers:systematic-debugging` for slowness-as-a-bug (reproduce, measure, hypothesize).

## Red flags

| Thought | Reality |
|---|---|
| "Loading each node in the loop is fine for now" | It is O(n) queries; `loadMultiple()` is one. |
| "Add max-age 0, it changes often" | Use tags; max-age 0 disables caching for the whole page. |
| "Turn on Redis" | Without a measured bottleneck it moves the cost, not the cause. |
| "Views is slow, rewrite it as a custom query" | Views caching and pagers usually fix it; a custom query loses access and cacheability. |
| "It's fast on my machine" | Cold cache, real data volume, and a non-local DB are the numbers that matter. |
