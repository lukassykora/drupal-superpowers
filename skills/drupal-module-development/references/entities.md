# Entity API

## Choosing (see drupal-architecture decision tables)
Content entity: user data, revisions, translations, fields, Views. Config entity: deployable definitions. Custom tables only for high-volume non-content data (logs, counters) and still behind a service with access decisions.

## Content entity type (minimum viable, 11.x attribute style)

```php
#[ContentEntityType(
  id: 'saved_list',
  label: new TranslatableMarkup('Saved list'),
  handlers: [
    'access' => SavedListAccessControlHandler::class,
    'list_builder' => SavedListListBuilder::class,
    'form' => ['default' => SavedListForm::class, 'delete' => ContentEntityDeleteForm::class],
    'route_provider' => ['html' => AdminHtmlRouteProvider::class],
    'views_data' => EntityViewsData::class,
  ],
  base_table: 'saved_list',
  data_table: 'saved_list_field_data',
  revision_table: 'saved_list_revision',
  translatable: TRUE,
  admin_permission: 'administer saved lists',
  revision_metadata_keys: ['revision_user' => 'revision_uid', 'revision_created' => 'revision_timestamp', 'revision_log_message' => 'revision_log'],
  revision_data_table: 'saved_list_field_revision',
  entity_keys: ['id' => 'id', 'revision' => 'vid', 'label' => 'title', 'uuid' => 'uuid', 'owner' => 'uid', 'langcode' => 'langcode', 'published' => 'status'],
  links: ['canonical' => '/saved-list/{saved_list}', 'edit-form' => '/saved-list/{saved_list}/edit', 'delete-form' => '/saved-list/{saved_list}/delete', 'collection' => '/admin/content/saved-lists'],
  field_ui_base_route: 'entity.saved_list.settings',
)]
final class SavedList extends EditorialContentEntityBase implements EntityOwnerInterface, EntityPublishedInterface {
  use EntityOwnerTrait; use EntityPublishedTrait; use EntityChangedTrait;
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type) + static::ownerBaseFieldDefinitions($entity_type) + static::publishedBaseFieldDefinitions($entity_type);
    $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Changed'))->setTranslatable(TRUE)->setRevisionable(TRUE);
    $fields['title'] = BaseFieldDefinition::create('string')->setLabel(t('Title'))->setRequired(TRUE)->setTranslatable(TRUE)->setRevisionable(TRUE)->setSetting('max_length', 255)->setDisplayOptions('form', ['type' => 'string_textfield'])->setDisplayConfigurable('form', TRUE);
    return $fields;
  }
}
```
- Annotation form (`@ContentEntityType`) for all 10.x and 11.0: entity-type attributes exist only from 11.1 (`core/lib/Drupal/Core/Entity/Attribute/`); verify against the installed core's `Node` class. `EditorialContentEntityBase` requires `revision_metadata_keys` (its trait throws `UnsupportedEntityTypeDefinitionException` otherwise).
- Schema is generated from field definitions; adding a base field to an existing site needs `hook_update_N` with `\Drupal::entityDefinitionUpdateManager()->installFieldStorageDefinition(...)`.
- Bundles: `bundle_entity_type` pointing to a config entity type, or `bundle` key alone for hard-coded bundles.

## Access control handler

```php
final class SavedListAccessControlHandler extends EntityAccessControlHandler {
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    $is_owner = $account->id() && (int) $entity->getOwnerId() === (int) $account->id();   // getOwnerId() is a string from storage
    return match ($operation) {
      'view' => AccessResult::allowedIf($is_owner || $entity->isPublished())->addCacheableDependency($entity)->cachePerUser(),
      'update', 'delete' => AccessResult::allowedIf($is_owner)->cachePerUser()->addCacheableDependency($entity),
      default => AccessResult::neutral(),
    };
  }
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'use saved items');
  }
}
```
Every `AccessResult` carries cacheability (`cachePerUser`, `cachePerPermissions`, `addCacheableDependency`). Field-level: `checkFieldAccess()`.

## Config entity type
`#[ConfigEntityType(id:, label:, config_prefix:, entity_keys: ['id' => 'id', 'label' => 'label'], config_export: ['id', 'label', 'settings'], handlers: [...], admin_permission:, links: [...])]` + `config/schema` mapping `<module>.<config_prefix>.*`. Core: `modules/user/src/Entity/Role.php`.

## Storage, queries, loading
- `$storage->load()`/`loadMultiple()` do **not** check access; filter with `$entity->access('view', $account)` before output.
- Entity query: `$storage->getQuery()->accessCheck(TRUE)->condition(...)->range(0, 50)->execute()`; `accessCheck()` is mandatory (10.0+). `accessCheck(FALSE)` only for admin/system contexts and say why.
- Revisions: `loadRevision()`, `getLatestRevisionId()`; with content_moderation the default revision may not be the latest.
- Translations: `$entity->hasTranslation($langcode)`, `getTranslation()`; query per language with `->condition('langcode', ...)`.
- Validation: `$violations = $entity->validate();` before save in non-form code.
- Cache tags: entity `getCacheTags()` (`node:1`), list tags `node_list` (and `node_list:<bundle>` in 10.1+); invalidate custom tags with `Cache::invalidateTags()`.

## Testing
Kernel test with `$modules = ['saved_items', 'user', 'system', ...]`, `installEntitySchema('saved_list')`, `installConfig(['saved_items'])`; assert access per operation for owner vs stranger. Core example `modules/node/tests/src/Kernel/NodeAccessTest.php`.
