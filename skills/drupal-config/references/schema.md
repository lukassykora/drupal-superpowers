# Config schema

Every `config/install/*.yml`, every config entity type, every block/plugin setting, and every third-party setting needs a schema entry. Kernel tests enforce it (`strictConfigSchema`), config validation (10.2+ constraints) and translation depend on it.

## Simple config
```yaml
# config/install/saved_items.settings.yml
max_items: 100
notify: true
labels:
  button: 'Save'
```
```yaml
# config/schema/saved_items.schema.yml
saved_items.settings:
  type: config_object
  label: 'Saved items settings'
  mapping:
    max_items:
      type: integer
      label: 'Maximum items'
      constraints:
        Range:
          min: 1
          max: 1000
    notify:
      type: boolean
      label: 'Notify on save'
    labels:
      type: mapping
      label: 'Labels'
      mapping:
        button:
          type: label
          label: 'Button label'
```
Types: `string`, `label` (translatable, single line), `text` (translatable, multiline), `integer`, `float`, `boolean`, `uri`, `email`, `path`, `date_format`, `mapping`, `sequence` (with `type` of items), `ignore` (only when unavoidable, documented). `config_object` for simple config, `config_entity` for entities.

## Config entity
```yaml
saved_items.preset.*:
  type: config_entity
  label: 'Saved items preset'
  mapping:
    id: { type: string, label: 'ID' }
    label: { type: label, label: 'Label' }
    settings:
      type: mapping
      mapping:
        limit: { type: integer, label: 'Limit' }
```
The mapping must list every key in `config_export` of the entity type. `status`, `uuid`, `langcode`, `dependencies`, `third_party_settings` come from the `config_entity` base type.

## Block and plugin settings
```yaml
block.settings.greeting_block:
  type: block_settings
  label: 'Greeting block settings'
  mapping:
    greeting: { type: label, label: 'Greeting' }
```
Field types: `field.storage_settings.<type>`, `field.field_settings.<type>`, `field.value.<type>`; widgets/formatters: `field.widget.settings.<id>`, `field.formatter.settings.<id>`.

## Third-party settings
```yaml
node.type.*.third_party.saved_items:
  type: mapping
  mapping:
    enabled: { type: boolean, label: 'Enabled' }
```

## Dependencies in exported config
```yaml
dependencies:
  module: [node]
  config: [node.type.article]
  enforced:
    module: [saved_items]      # removed when the module is uninstalled
```
Missing or wrong dependencies cause import order failures and orphaned config on uninstall.

## Verification
- Kernel test with the module installed and `installConfig(['<module>'])`: missing schema throws.
- `drush config:inspect` (Config Inspector contrib) if the project has it; otherwise `\Drupal::service('config.typed')->get('name')->validate()` via `php:eval` on LOCAL.
- Core references: `modules/system/config/schema/system.schema.yml`, `core/config/schema/core.data_types.schema.yml`.
