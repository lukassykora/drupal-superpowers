# Core examples for cacheability

Read the file in the installed core; signatures vary by version.

| Case | Core file | What to copy |
|---|---|---|
| Block varying per user/route | `modules/user/src/Plugin/Block/UserLoginBlock.php` | `blockAccess()` adds `route.name` + `user.roles:anonymous` to the `AccessResult`; `build()` adds `url.path`/`url.query_args` in `#cache` |
| Block varying per permissions/route | `modules/system/src/Plugin/Block/SystemMenuBlock.php` | contexts from the menu tree, `getCacheTags()` merge |
| Personalized part behind a lazy builder | `modules/comment/src/CommentLazyBuilders.php`, `modules/comment/src/CommentViewBuilder.php` | `#lazy_builder`, `TrustedCallbackInterface`, contexts on the callback result |
| Controller output with entity dependencies | `modules/node/src/Controller/NodeController.php` (`addPage`) | `addCacheableDependency`, list tags |
| Cacheable JSON | `core/lib/Drupal/Core/Cache/CacheableJsonResponse.php`; usage in `modules/system/src/Controller/LinksetController.php` | response-level dependencies |
| Access result with metadata | `modules/node/src/NodeAccessControlHandler.php`, `core/lib/Drupal/Core/Access/AccessResult.php` | `cachePerPermissions()`, `cachePerUser()`, `addCacheableDependency()` |
| Custom cache context | `core/lib/Drupal/Core/Cache/Context/UserCacheContext.php`, `QueryArgsCacheContext.php` | `CacheContextInterface`, `getCacheableMetadata()` |
| Tag invalidation on data change | `core/lib/Drupal/Core/Entity/EntityBase.php` (`postSave` → `invalidateTagsOnSave`) | list tags and entity tags invalidation |
| Views cacheability | `modules/views/src/Plugin/views/cache/Tag.php` | tag-based caching of results and output |
| Placeholders / BigPipe | `modules/big_pipe/src/Render/BigPipe.php`, `core/lib/Drupal/Core/Render/PlaceholderingRenderCache.php` | how placeholders keep pages cacheable |
| Config-derived output | `modules/system/src/Plugin/Block/SystemBrandingBlock.php` | `config:system.site` tag via `getCacheTags()` merging `$this->configFactory->get('system.site')->getCacheTags()` |
| Kernel test of cache metadata | `modules/block/tests/src/Kernel/BlockViewBuilderTest.php` | asserting contexts/tags/max-age on the build |
| Functional cache assertions | `modules/system/tests/src/Functional/Cache/AssertPageCacheContextsAndTagsTrait.php`, `modules/node/tests/src/Functional/NodeCacheTagsTest.php` | `assertCacheContexts`, `assertCacheTags` |
