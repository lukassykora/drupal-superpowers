---
verified_against: { drupal: "11.4.x" }
last_reviewed: 2026-09-04
sources: [drupal core source tree]
---

# "How does core do it?" index

Paths are relative to the project's `core/` directory and hold for 10.x–11.x unless noted. Use them as the
starting point for `drupal-lookup --kind pattern`, then read the file in the *installed* version, because
signatures and attributes differ between branches. When a path is missing in the installed core, say so;
do not reconstruct the pattern from memory.

| Pattern | Core example to read | Notes |
|---|---|---|
| Content entity type with bundles, revisions, translations | `modules/node/src/Entity/Node.php`, `modules/node/src/NodeAccessControlHandler.php` | attribute `#[ContentEntityType]` in 11.x (annotation in ≤10.1) |
| Simple content entity without bundles | `modules/user/src/Entity/User.php`, `modules/comment/src/Entity/Comment.php` | |
| Config entity + list builder + form | `modules/user/src/Entity/Role.php`, `modules/user/src/RoleListBuilder.php`, `modules/user/src/RoleForm.php`, `modules/user/config/schema/user.schema.yml` | |
| Config schema for simple config | `modules/system/config/schema/system.schema.yml` | pair every `config/install/*.yml` with schema |
| Entity access control handler | `modules/node/src/NodeAccessControlHandler.php`, `modules/media/src/MediaAccessControlHandler.php` | returns `AccessResult` with cacheability |
| Route access check service | `modules/user/src/Access/RegisterAccessCheck.php`, `lib/Drupal/Core/Entity/EntityAccessCheck.php` (`_entity_access`) | prefer `_entity_access` / `_permission` over custom checks |
| Controller with DI | `modules/node/src/Controller/NodeController.php` | `ContainerInjectionInterface::create()`; 10.3+ autowire via `services.yml` `autowire: true` |
| Block plugin with cacheability | `modules/user/src/Plugin/Block/UserLoginBlock.php`, `modules/system/src/Plugin/Block/SystemMenuBlock.php` | `getCacheContexts()`, `getCacheTags()`, `getCacheMaxAge()` |
| Lazy builder / placeholder | `modules/comment/src/CommentLazyBuilders.php`, `lib/Drupal/Core/Render/Placeholder/` | `#lazy_builder` + `#create_placeholder`; implements `TrustedCallbackInterface` |
| Cacheable response / JSON | `lib/Drupal/Core/Cache/CacheableJsonResponse.php`, `modules/system/src/Controller/` | add `CacheableMetadata` to the response |
| Event subscriber | `modules/system/src/EventSubscriber/`, `lib/Drupal/Core/EventSubscriber/` | tagged `event_subscriber` |
| Route subscriber (alter routes) | `modules/node/src/Routing/RouteSubscriber.php`, `lib/Drupal/Core/Routing/RouteSubscriberBase.php` | |
| Form with validation, AJAX | `modules/user/src/Form/UserPasswordForm.php`, `modules/system/src/Form/SiteInformationForm.php` (ConfigFormBase), `modules/file/src/Element/ManagedFile.php` (AJAX) | |
| Permissions from YAML and from a callback | `modules/node/node.permissions.yml`, `modules/node/src/NodePermissions.php` | |
| Queue worker | `modules/aggregator` (removed 10.x) → `lib/Drupal/Core/Queue/QueueWorkerInterface.php`, `modules/locale/src/Plugin/QueueWorker/LocaleTranslation.php` | `#[QueueWorker]` attribute 11.x |
| Cron hook | `modules/node/node.module` `node_cron()`, `modules/system/system.module` | |
| Batch API | `modules/system/src/Form/ModulesListConfirmForm.php`, `lib/Drupal/Core/Batch/` | |
| hook_update_N and post_update | `modules/system/system.install`, `modules/system/system.post_update.php` | post updates for data, update hooks for schema |
| OOP hook (11.1+) | `modules/user/src/Hook/UserHooks.php`, `lib/Drupal/Core/Hook/Attribute/Hook.php` | `#[LegacyHook]` for 10.x shims |
| Plugin manager + attribute + annotation fallback | `lib/Drupal/Core/Block/BlockManager.php`, `lib/Drupal/Core/Block/Attribute/Block.php`, `lib/Drupal/Core/Block/Annotation/Block.php` | `AttributeDiscoveryWithAnnotations` |
| Plugin derivatives | `modules/system/src/Plugin/Derivative/SystemMenuBlock.php` | |
| Field type / widget / formatter | `modules/text/src/Plugin/Field/FieldType/TextItem.php`, `modules/text/src/Plugin/Field/FieldWidget/`, `modules/text/src/Plugin/Field/FieldFormatter/` | |
| Entity query with access | `modules/node/src/NodeStorage.php`, any `->getQuery()->accessCheck(TRUE)` in `modules/*/src` | `accessCheck()` mandatory since 10.0 |
| Typed data / constraints | `lib/Drupal/Core/Validation/Plugin/Validation/Constraint/`, `modules/user/src/Plugin/Validation/Constraint/` | |
| Twig escaping and `#plain_text` | `lib/Drupal/Core/Render/Element/HtmlTag.php`, `lib/Drupal/Core/Template/TwigExtension.php` | never `\|raw` on user input |
| Kernel test for a service/entity | `modules/node/tests/src/Kernel/NodeAccessTest.php`, `tests/Drupal/KernelTests/KernelTestBase.php` | |
| Functional test for a route + permission | `modules/node/tests/src/Functional/NodeAccessTest.php`, `modules/system/tests/src/Functional/` | `drupalLogin`, `assertSession()->statusCodeEquals(403)` |
| Functional JavaScript test | `modules/system/tests/src/FunctionalJavascript/`, `tests/Drupal/FunctionalJavascriptTests/WebDriverTestBase.php` | |
| Unit test with mocks | `tests/Drupal/Tests/Core/`, `modules/user/tests/src/Unit/` | avoid mocking the container wholesale |
| Migrate source/process/destination | `modules/migrate/src/Plugin/migrate/process/`, `modules/migrate_drupal/` (removed in 12.0 alpha) , `modules/user/migrations/d7_user.yml` | |
| Library definition + Drupal.behaviors + once() | `modules/system/system.libraries.yml`, `misc/drupal.js`, `modules/toolbar/js/toolbar.js` | |
| Recipe | `recipes/` in core (10.3+), `lib/Drupal/Core/Recipe/Recipe.php` | |
| State vs config vs tempstore | `modules/system/src/Form/CronForm.php` (state), `modules/user/src/PrivateTempStoreFactory` usage in `modules/views_ui` | |
