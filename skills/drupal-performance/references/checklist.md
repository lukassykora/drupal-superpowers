# Performance checklist

| Area | Look for | Fix |
|---|---|---|
| Entity loads | `->load($id)` inside `foreach`; loading referenced entities per row via `getStorage()->load($item['target_id'])`; `loadByProperties()` in loops | collect IDs, one `loadMultiple()`; `$node->get('field_tags')->referencedEntities()` (batch-loads); entity queries with `range()`; view builders for lists |
| Queries | `->query()`/`select()` inside loops; `COUNT(*)` per row; `SELECT *` on large tables; missing `range()`/pager; `LIKE '%x%'` on big columns | hoist out of the loop, aggregate once, add pager, index (`hook_schema` `indexes` + update hook), use entity queries with conditions |
| Views | no caching, no pager, many relationships, `DISTINCT` on large joins, rendered entity rows with heavy view modes | tag-based cache, pager, fewer relationships, lighter view mode, Views aggregation |
| Render / cache | `max-age 0` on pages, uncacheable contexts (`session`, `cookies`, `headers`) on anonymous pages, personalized content in the page instead of a lazy builder, Dynamic Page Cache misses (`X-Drupal-Dynamic-Cache: MISS` repeatedly) | contexts/tags, lazy builders + BigPipe, `CacheableResponse` |
| Hooks and subscribers | `hook_entity_load`, `hook_node_view`, `hook_preprocess_page`, request/response subscribers doing queries or HTTP calls on every request | move to lazily computed render arrays with cache, or to cron/queue |
| External calls | Guzzle in controllers/preprocess without cache, no timeout, retries in request | cache the result (Cache API with max-age/tags), `timeout`, queue for writes, circuit breaker |
| Cron / queues / batch | one giant cron hook; queue items processing hundreds of entities; migrations loading full entities where IDs suffice | queue workers with small items, `hook_cron` enqueues only, batch API with `$sandbox`, migration `--limit`/high-water |
| Memory | `loadMultiple()` of thousands of entities; building full render arrays for exports; entity memory cache growth in long CLI runs | chunk IDs (`array_chunk`), `resetCache()` between chunks, generators, `drush --memory-limit` is a symptom not a fix |
| Assets | unaggregated CSS/JS on non-LOCAL, large images without image styles, no lazy loading | aggregation on, image styles/responsive image, `loading="lazy"` (core default for images) |
| Database | missing indexes on custom tables, `text` columns in conditions, no `PRIMARY KEY` | schema indexes, normalized columns |
| Config / container | services with heavy constructors instantiated on every request | lazy services (`lazy: true`), service closures |
| Search | Views with `LIKE` for search; entity queries for full-text | Search API with a backend, indexes |

Observability signals: `X-Drupal-Cache`, `X-Drupal-Dynamic-Cache` headers; watchdog `php` warnings about memory; slow query log; Webprofiler toolbar (database, cache, render panels) when installed; `drush queue:list` backlog.
