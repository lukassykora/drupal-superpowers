---
name: drupal-cacheability
description: Use when Drupal code builds render arrays, responses, blocks, lazy builders, or output that depends on the current user, permissions, roles, language, query parameters, or entities, and when a page or block shows stale or another user's data.
---

# Drupal cacheability

**Core principle:** cache metadata describes what the output depends on. Missing metadata is a correctness bug (wrong content for the next visitor), not a performance detail. Every render array, response, and access result declares contexts, tags, and max-age; the cache system does the rest.

## When to use

Writing or reviewing anything rendered or returned by Drupal; symptoms like "shows the previous user's name", "changes appear only after cache clear", "wrong language", "personalized block cached for anonymous". Not for pure data-layer code without output.

## Procedure

1. **List the variations**: what does this output change with? Identity (`user`), permissions (`user.permissions`), roles (`user.roles`), language (`languages:language_interface`), URL/path (`url.path`, `url.query_args`, `route`), request (`headers`, `cookies:...`, `session`), time. Each becomes a **cache context**.
2. **List the invalidations**: which entities/config/lists appear in the output? Each gives **cache tags** (`node:1`, `node_list`, `config:system.site`, `user:3`, custom tags you invalidate yourself).
3. **Decide max-age**: `Cache::PERMANENT` unless the output expires by time; `0` only when nothing else expresses the variation (and then consider a lazy builder so the rest of the page stays cacheable).
4. **Attach the metadata** ([references/metadata-rules.md](references/metadata-rules.md)): `#cache` keys, `CacheableMetadata::createFromObject($entity)->applyTo($build)`, `addCacheableDependency()`, `BubbleableMetadata` for responses and `AccessResult`s, block plugin `getCacheContexts()/getCacheTags()/getCacheMaxAge()`, `CacheableJsonResponse` for JSON.
5. **Isolate the personalized part** with a `#lazy_builder` + `#create_placeholder` when the page is otherwise cacheable (BigPipe renders it late); the lazy builder's own metadata still needs the context.
6. **Verify**: Kernel test asserting `getCacheContexts()`/tags on the build or block; L3 `X-Drupal-Dynamic-Cache` HIT/MISS and two-user comparison (`drupal-runtime-verification`). Record `VERIFY` lines.

## Decision rules

- Output mentions the current user (name, picture, their items) → `user` context (not `user.permissions`).
- Output depends on a permission or role check → `user.permissions` (preferred over `user.roles`).
- Output depends on a query parameter → `url.query_args:<name>`; on the path → `url.path` or `route`.
- Output lists entities → the list cache tag (`node_list`, or `node_list:<bundle>` on 10.1+) plus each entity's tags.
- Output from config → `config:<name>` tag (automatic when you use `addCacheableDependency($config)`).
- `max-age: 0` on a whole page is a bug in disguise unless justified in a comment; Internal Page Cache ignores max-age for anonymous, so time-based expiry needs a tag strategy or `Cache-Control` handling.
- Access results carry cacheability too: `AccessResult::allowedIf(...)->cachePerUser()` etc.
- Core examples for each case in [references/core-examples.md](references/core-examples.md); confirm in the installed version.

## Works with process skills

Inside design (`superpowers:brainstorming` / `drupal-architecture`) this fills the cacheability row. In debugging, it is the family "render / cache" of `drupal-debugging`. Reviews use it as the cacheability lens.

## Red flags

| Thought | Reality |
|---|---|
| "Set max-age 0 to be safe" | Kills caching for the whole page and still leaks through Internal Page Cache for anonymous. |
| "The block is per user, disable block caching" | Add the `user` context; the render cache handles the rest. |
| "It only shows after `drush cr`, so cache is broken" | The tag that should invalidate it is missing. |
| "Anonymous users all see the same thing" | Not with query-args, language, or session-dependent output. |
| "JSON responses aren't cached" | `CacheableJsonResponse` is; plain `JsonResponse` bypasses Dynamic Page Cache, but the browser/CDN may still cache it: set headers. |
