# Cache metadata rules

## Where metadata lives
| Object | API |
|---|---|
| Render array | `'#cache' => ['contexts' => [...], 'tags' => [...], 'max-age' => N, 'keys' => [...]]`; `CacheableMetadata::createFromRenderArray($build)`, `->applyTo($build)` |
| Entity / config / anything `CacheableDependencyInterface` | `CacheableMetadata::createFromObject($obj)`; `$metadata->addCacheableDependency($obj)` |
| Block plugin | `getCacheContexts()`, `getCacheTags()`, `getCacheMaxAge()` (merge with `parent::`); `blockAccess()` returns `AccessResult` with metadata |
| Response | `CacheableResponse`, `CacheableJsonResponse`, `CacheableRedirectResponse` + `addCacheableDependency()`; plain `Response` is uncacheable by Dynamic Page Cache |
| AccessResult | `->cachePerUser()`, `->cachePerPermissions()`, `->addCacheContexts([...])`, `->addCacheTags([...])`, `->setCacheMaxAge()`, `->addCacheableDependency($entity)` |
| Lazy builder | the callback's returned render array carries its own `#cache`; placeholder inherits nothing else |
| Twig | metadata bubbles automatically from render arrays; helpers that fetch data in preprocess must add it to `$variables['#cache']` |

Metadata **bubbles up**: child metadata merges into parents during rendering (`BubbleableMetadata`). Anything computed outside rendering (preprocess, controllers, services) must be attached explicitly.

## Contexts (most used)
| Context | Use when output varies by |
|---|---|
| `user` | the identity (name, own content); implies everything below |
| `user.permissions` | permissions (hashed) |
| `user.roles`, `user.roles:authenticated`, `user.roles:anonymous` | roles; prefer permissions |
| `languages:language_interface`, `languages:language_content` | language |
| `url`, `url.path`, `url.query_args`, `url.query_args:page`, `route`, `route.name` | request URL / route |
| `theme` | active theme |
| `timezone` | user timezone |
| `session`, `cookies:<name>`, `headers:<name>`, `ip` | request data (expensive; avoid on anonymous pages) |
| custom | `cache.context` service tag; implement `CacheContextInterface` / `CalculatedCacheContextInterface` |

## Tags
- Entity: `<entity_type>:<id>`; list: `<entity_type>_list`, `<entity_type>_list:<bundle>` (10.1+); config: `config:<name>`; theme: `rendered`; custom: any string, invalidated with `Cache::invalidateTags(['saved_items:3'])` from the code that changes the data (entity presave/delete hooks, service methods).
- Views: `views_data` plus the entity list tags of its base table.
- Never rely on a cron-driven expiry when a tag can express the change.

## Max-age
- `Cache::PERMANENT` (-1) by default. A positive max-age for time-bound output (external API results). `0` for uncacheable output and only on the smallest element, ideally behind a lazy builder.
- Internal Page Cache stores anonymous pages permanently and ignores render max-age (https://www.drupal.org/node/2352009); `system.performance` `cache.page.max_age` only sets the outgoing `Cache-Control` header for browsers/proxies. Tags are the only correct invalidation for anonymous pages.

## Lazy builders and BigPipe
```php
$build['greeting'] = [
  '#lazy_builder' => ['greeting_block.lazy_builders:greeting', [$account->id()]],
  '#create_placeholder' => TRUE,
];
```
- Service method or static method on a class implementing `TrustedCallbackInterface`; arguments scalar only.
- The callback returns a render array with its own `#cache` (e.g. `contexts: ['user']`); the surrounding page stays cacheable and BigPipe streams the placeholder.

## Testing
- Kernel: build the block/controller output and assert `CacheableMetadata::createFromRenderArray($build)->getCacheContexts()` contains `user`; for blocks assert `$plugin->getCacheContexts()`.
- Functional: `$this->assertCacheContext('user')`, `$this->assertCacheTags(['node:1'])` (`Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait`; `assertCacheTags` includes default tags unless the second argument is FALSE); `drupalGet` twice and inspect `X-Drupal-Dynamic-Cache`.
- L3: two users, same URL, compare the personalized fragment; headers `X-Drupal-Cache`, `X-Drupal-Dynamic-Cache`, `Cache-Control`.
