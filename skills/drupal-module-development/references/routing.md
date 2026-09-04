# Routing and controllers

## `<module>.routing.yml`

```yaml
saved_items.list:
  path: '/user/{user}/saved-items'
  defaults:
    _controller: 'Drupal\saved_items\Controller\SavedItemsController::list'
    _title: 'Saved items'
  requirements:
    _permission: 'use saved items'
    _entity_access: 'user.view'
    user: \d+
  options:
    parameters:
      user:
        type: entity:user
    no_cache: false            # TRUE only for genuinely uncacheable responses
saved_items.api:
  path: '/api/saved-items'
  defaults:
    _controller: 'Drupal\saved_items\Controller\SavedItemsController::api'
  methods: [GET]
  requirements:
    _permission: 'use saved items'
    _format: 'json'
    _user_is_logged_in: 'TRUE'
```

### Access requirements (pick the strongest that fits; combine when needed)
| Requirement | Use |
|---|---|
| `_permission: 'x'` (comma = AND, `+` = OR) | most routes |
| `_entity_access: 'node.update'` | routes with an entity parameter; runs the entity access handler |
| `_custom_access: '\Drupal\m\Access\FooAccess::access'`, or a custom access checker service tagged `{ name: access_check, applies_to: _saved_items_access }` with the route requirement `_saved_items_access: 'TRUE'` | complex rules; return `AccessResult` with cacheability |
| `_role: 'administrator'` | rarely; permissions are better |
| `_user_is_logged_in: 'TRUE'` | authenticated-only |
| `_csrf_token: 'TRUE'` | state-changing GET links (prefer POST forms) |
| `_access: 'TRUE'` | public content only, stated explicitly in a comment |

Route access is not entity access: after loading entities in the controller, check `$entity->access('view')` or use entity queries with `accessCheck(TRUE)`.

### Parameters
- `{node}` with `options.parameters.node.type: entity:node` upcasts; missing entity → 404 automatically.
- Custom converters: `paramconverter` service tag.
- Route requirements regex (`node: \d+`) prevent junk hitting the converter.

## Controllers

```php
final class SavedItemsController extends ControllerBase {
  public function __construct(private readonly SavedItemsRepository $repository) {}
  public static function create(ContainerInterface $container): static {
    return new static($container->get('saved_items.repository'));
  }
  public function list(UserInterface $user): array {
    $ids = $this->repository->getSavedNodeIds($user);
    $nodes = $this->entityTypeManager()->getStorage('node')->loadMultiple($ids);
    $nodes = array_filter($nodes, fn (NodeInterface $n) => $n->access('view'));
    $build = ['#theme' => 'item_list', '#items' => array_map(fn ($n) => $n->toLink(), $nodes)];
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['user'])->addCacheTags(['node_list', 'saved_items:' . $user->id()]);
    foreach ($nodes as $node) { $cache->addCacheableDependency($node); }
    $cache->applyTo($build);
    return $build;
  }
  public function api(): CacheableJsonResponse {
    $ids = $this->repository->getSavedNodeIds($this->currentUser());
    $response = new CacheableJsonResponse(['items' => $ids]);
    $response->addCacheableDependency((new CacheableMetadata())->addCacheContexts(['user'])->addCacheTags(['saved_items:' . $this->currentUser()->id()]));
    return $response;
  }
}
```
- Return render arrays for pages, `CacheableJsonResponse`/`CacheableResponse` for cacheable responses, plain `JsonResponse` only with `max-age: 0` semantics acknowledged.
- Titles via `_title` or `_title_callback`; never build HTML strings.
- Redirects: `$this->redirect('route.name', [...])`; external → `TrustedRedirectResponse` only for trusted URLs.

## Route subscribers (altering others' routes)

`src/Routing/RouteSubscriber.php` extends `RouteSubscriberBase`, `alterRoutes(RouteCollection $collection)`; registered as an event subscriber. Use for tightening access on core routes; core example `modules/node/src/Routing/RouteSubscriber.php`.

## Verification
- `drush route` (all routes), `drush route --name=<name>` or `--path=<path>` (Drush 10.5+) lists routes; `drush cr` rebuilds.
- Functional test: anonymous → 403/302, user without permission → 403, with permission → 200 (`drupal-testing`).
