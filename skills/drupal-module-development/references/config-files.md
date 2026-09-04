# Module YAML files

Only create the file when the feature needs it. Validate YAML with `php -r 'print_r(yaml_parse_file(...))'` or `drush` container rebuild.

## `<module>.info.yml` (always)

```yaml
name: Saved items
type: module
description: Lets users keep a private list of saved nodes.
package: Custom
core_version_requirement: ^11
dependencies:
  - drupal:node
  - drupal:user
# optional: configure: saved_items.settings   (route to the settings form)
# optional: php: 8.3
```
- `core_version_requirement` uses Composer syntax; `^10.3 || ^11` only when both are tested.
- Dependencies are `drupal:<module>` for core, `<project>:<module>` for contrib.
- Never add `version:` in custom modules (packaging adds it for contrib).

## `<module>.permissions.yml`

```yaml
use saved items:
  title: 'Use saved items'
  description: 'Save nodes to a personal list.'
administer saved items:
  title: 'Administer saved items'
  restrict access: true
```
- `restrict access: true` for anything admin-level (shows the warning in the UI).
- Dynamic permissions: `permission_callbacks:` → `\Drupal\<module>\Permissions::permissions` (core example: `node.permissions.yml`).

## `<module>.links.menu.yml` / `.links.task.yml` / `.links.action.yml`

```yaml
saved_items.settings:
  title: 'Saved items'
  parent: system.admin_config_content
  route_name: saved_items.settings
  description: 'Configure saved items.'
```
- Task (tabs) need `base_route`; action links need `appears_on`.

## `<module>.libraries.yml`

```yaml
saved_items.toggle:
  version: 1.x
  js:
    js/toggle.js: {}
  css:
    component:
      css/toggle.css: {}
  dependencies:
    - core/drupal
    - core/once
```
- Attach with `#attached['library'][] = 'saved_items/saved_items.toggle'`; JS uses `Drupal.behaviors` + `once()`.

## `config/install/<module>.settings.yml` + `config/schema/<module>.schema.yml` (together, always)

```yaml
# config/install/saved_items.settings.yml
max_items: 100
```
```yaml
# config/schema/saved_items.schema.yml
saved_items.settings:
  type: config_object
  label: 'Saved items settings'
  mapping:
    max_items:
      type: integer
      label: 'Maximum items per user'
```
- Config entities need `config/schema` with `type: config_entity` and their `config_prefix` mapping.
- `config/optional/` for config that depends on modules that may be absent.
- Install-time config is imported once; changes to existing sites go through `hook_post_update_NAME` or config export from the site.

## `<module>.install`

- `hook_schema()` for custom tables (prefer entities); `hook_install()`/`hook_uninstall()` for one-off setup; `hook_requirements($phase)` for runtime checks.
- `hook_update_N()`: schema and structural changes, numbered `<major><minor><seq>` style the project uses (e.g. `11401`); idempotent; return a translatable message.
- `<module>.post_update.php`: `hook_post_update_NAME(&$sandbox)` for data/config updates; batchable via `$sandbox`.

## `<module>.module`

Only when a procedural hook is needed (theme hooks, help, alters on ≤ 11.0, or when the project keeps hooks procedural). On ≥ 11.1 with OOP hooks in use, prefer `src/Hook/<Module>Hooks.php` (see hooks.md).
