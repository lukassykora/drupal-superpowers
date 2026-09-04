# Hooks

## Style by version (facts registry: `oop-hooks`, `procedural-hooks-not-deprecated`)
| Project | Write hooks as |
|---|---|
| ≤ 11.0 | procedural functions in `<module>.module` |
| ≥ 11.1 and the module (or project convention) uses `src/Hook/` | `#[Hook]` methods in `src/Hook/<Module>Hooks.php`, autowired service |
| ≥ 11.1, module has only procedural hooks | keep procedural unless the task is the conversion; never convert unrequested |
| any version | `hook_install`, `hook_update_N`, `hook_post_update_*`, `hook_requirements`, `hook_theme`, `hook_hook_info`, `hook_module_implements_alter` stay procedural (theme hooks became OOP-capable for *themes* in 11.3; check `drupal-facts`) |

## Procedural

```php
/**
 * Implements hook_entity_presave().
 */
function saved_items_entity_presave(EntityInterface $entity): void {
  if ($entity instanceof NodeInterface && $entity->isNew()) {
    \Drupal::service('saved_items.repository')->notifyNew($entity);   // \Drupal:: is acceptable here
  }
}
```
The docblock line `Implements hook_NAME().` is required by coding standards.

## OOP (11.1+)

```php
namespace Drupal\saved_items\Hook;

use Drupal\Core\Hook\Attribute\Hook;

final class SavedItemsHooks {
  public function __construct(private readonly SavedItemsRepository $repository) {}

  #[Hook('entity_presave')]
  public function entityPresave(EntityInterface $entity): void { ... }

  #[Hook('form_alter')]                       // or #[Hook('form_node_article_edit_form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void { ... }
}
```
- Register nothing: classes in `src/Hook/` are discovered; they are autowired services.
- 10.x compatibility while converting: keep the procedural function with `#[LegacyHook]` delegating to the class.
- Ordering (11.2+): `#[Hook('form_alter', order: Order::Last)]`, `#[ReorderHook]`, `#[RemoveHook]` — verify in installed core before use.

## Finding the right hook
- `grep -rn "function hook_" <docroot>/core/modules/<module>/<module>.api.php` and `core/lib/Drupal/Core/**/*.api.php`; `drupal-lookup hook_entity_presave --kind hook`.
- Prefer an event or a service decorator when core dispatches one (request/response lifecycle, config CRUD, migration events); prefer hooks for entity CRUD, form alters, theme/preprocess, cron, permissions.
- Alter hooks (`hook_*_alter`) change other modules' data structures; keep them small and documented.

## Common alters
| Goal | Hook |
|---|---|
| Change a form | `hook_form_alter`, `hook_form_FORM_ID_alter`, `hook_form_BASE_FORM_ID_alter` |
| Change entity access | `hook_entity_access`, `hook_ENTITY_TYPE_access`, `hook_entity_create_access` (return `AccessResult` with cacheability) |
| Add base fields to an existing type | `hook_entity_base_field_info` (+ update hook for existing sites) |
| Change routes | `RouteSubscriber` (not a hook) |
| Change render output | `hook_preprocess_HOOK`, `hook_ENTITY_TYPE_view`, `hook_entity_view_alter` |
| Change queries | `hook_query_TAG_alter`, `hook_entity_query_alter`? (check version) |
| Cron | `hook_cron` |

## Verification
- `drush cr` (hook implementation cache); `drush php:eval "var_dump(\Drupal::moduleHandler()->hasImplementations('entity_presave', 'saved_items'));"` (10.x+) read-only.
- Kernel test that triggers the hook (save an entity, submit a form) and asserts the effect.
