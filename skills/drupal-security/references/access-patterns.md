# Access patterns

Access is decided at four layers; a change is complete only when every layer that applies agrees.

## 1. Route
```yaml
requirements:
  _permission: 'use saved items'          # capability
  _entity_access: 'node.view'             # entity operation via the parameter
  _custom_access: '\Drupal\m\Access\ListAccess::access'   # complex; returns AccessResult
```
- Multiple requirements are AND-ed. `_permission: 'a,b'` = both; `'a+b'` = either.
- Custom access services: tag `access_check` with `applies_to: _my_check`; class implements `AccessInterface`, returns `AccessResult` **with cacheability** (`cachePerPermissions()`, `cachePerUser()`, `addCacheableDependency($entity)`).
- Public by design: `_access: 'TRUE'` plus a comment saying why; never for routes that read entities the viewer did not create.

## 2. Entity
- Load does not check access. After `load()`, `loadMultiple()`, `loadByProperties()`: `if ($entity->access('view', $account)) { ... }`; for lists, filter and add each entity as a cacheable dependency.
- Entity queries: `->accessCheck(TRUE)` (node access grants applied for nodes). `accessCheck(FALSE)` only in admin/system contexts and documented.
- Custom entity types: implement `EntityAccessControlHandler::checkAccess()` and `checkCreateAccess()`; `hook_ENTITY_TYPE_access` for cross-module rules; owner checks via `EntityOwnerInterface`.
- Node access grants (`hook_node_grants`/`hook_node_access_records`) when per-node visibility is needed; rebuilding grants is a deployment step.
- Revisions and moderation: "view latest version" (content_moderation) and "view any unpublished content" are separate permissions; check the operation you actually perform.

## 3. Field
- `$entity->get('field')->access('view'|'edit', $account)`; field formatters/widgets respect it, raw `->value` reads do not.
- `hook_entity_field_access` for field-level rules (e.g. hide internal notes from non-editors).

## 4. Operation-specific
- Forms: the route requirement guards the form; entity forms use entity access; confirm forms for deletes.
- AJAX callbacks and lazy builders run in the request of the page: their content must respect the same access as the page, and their cacheability must carry the access result's metadata.
- REST/JSON:API: authentication provider + permission; JSON:API applies entity and field access, but custom serializers bypass them.
- Drush/CLI and queue workers run as anonymous or uid 0/1: any user-facing consequence must re-check access for the target user, not the runner.

## Testing the access model
Functional: anonymous → 403 (or 302 to login), user without permission → 403, user with permission but not owner → 403 for owner-only operations, owner → 200. Kernel: `$this->assertFalse($entity->access('update', $stranger))`, and `AccessResult` reasons via `$entity->access('update', $stranger, TRUE)->getReason()`.

## Cacheability of access decisions
Every `AccessResult` and every render array that depends on access must carry `user.permissions` (permission-based), `user` (identity-based), `route`/`url` when the decision depends on the path, plus tags of the entities consulted. Missing metadata turns a correct access decision into a cached wrong one for the next visitor.
