# Services and dependency injection

## `<module>.services.yml`

```yaml
services:
  _defaults:
    autoconfigure: true        # 10.x+: registers event subscribers by interface
  saved_items.repository:
    class: Drupal\saved_items\SavedItemsRepository
    arguments: ['@database', '@entity_type.manager', '@current_user']
  # Autowiring (10.3+; check the project uses it before adopting):
  Drupal\saved_items\SavedItemsRepository:
    autowire: true
  # Alias so both styles resolve:
  saved_items.repository: '@Drupal\saved_items\SavedItemsRepository'
```

- Service IDs are `module.thing`, lowercase. Verify argument service IDs exist: `grep -rn "^  <id>:" <docroot>/core/core.services.yml <docroot>/core/modules/*/*.services.yml` (`entity.manager` was removed in 9.0; it is `entity_type.manager`).
- Class namespace must match the file path: `Drupal\<module>\Service\Foo` → `src/Service/Foo.php`; a mismatch is a `ServiceNotFoundException`/class-not-found at container compile.
- Tags: `event_subscriber`, `access_check` (with `applies_to`), `cache.context`, `route_enhancer`, `breadcrumb_builder`, `paramconverter`, `twig.extension`, `queue_worker` is a plugin not a tag.
- Decorators: `decorates: original.service`, `decoration_priority`, inner as `@saved_items.repository.inner`.
- Parameters: `parameters:` block; environment-specific values belong in settings.php `$settings`, not here.

## Injection patterns

| Class kind | Pattern | Core example |
|---|---|---|
| Service | constructor arguments from `services.yml` | any `core.services.yml` entry |
| Controller | `ControllerBase` + `public static function create(ContainerInterface $container)` returning `new static(...)`; or autowired constructor with `ContainerInjectionInterface` | `modules/node/src/Controller/NodeController.php` |
| Form | same as controller (`FormBase`), `ConfigFormBase` needs `$config_factory` (and `$typedConfigManager` on 10.2+) passed to parent | `modules/system/src/Form/SiteInformationForm.php` |
| Plugin (block, field, queue worker) | `ContainerFactoryPluginInterface::create($container, $configuration, $plugin_id, $plugin_definition)` | `modules/user/src/Plugin/Block/UserLoginBlock.php` |
| Event subscriber | constructor DI; `getSubscribedEvents()` | `modules/system/src/EventSubscriber/` |
| Procedural hook | `\Drupal::service('...')` is acceptable | `.module` files in core |
| `#[Hook]` class (11.1+) | constructor DI, autowired | `modules/user/src/Hook/UserHooks.php` |

Constructor promotion with `private readonly` is standard on PHP 8.1+ when the project uses it. Never call `\Drupal::` inside a class that has a `create()` method or constructor injection available.

## Event subscribers

```php
final class RedirectSubscriber implements EventSubscriberInterface {
  public static function getSubscribedEvents(): array {
    return [KernelEvents::REQUEST => ['onRequest', 30]];
  }
}
```
Register with `tags: [{ name: event_subscriber }]` unless `_defaults: autoconfigure: true` is set. Drupal-specific events: `ConfigEvents`, `EntityTypeEvents`, `MigrateEvents`, `RoutingEvents::ALTER` (route subscribers extend `RouteSubscriberBase`).

## Typical failures

- Wrong service ID / missing argument → container compile error on `drush cr`; read the exception, do not guess.
- Circular reference → split the service or inject the container-aware factory (`ClassResolver`, lazy `service_closure`).
- Private services or `@?optional` syntax for optional dependencies.
