# Plan task template (Drupal)

One task per file group; each step is one action with an exact command and the code it produces. The implementer sees only this task, so everything it needs is inside it: no "see §4", no "as in Task 1". Name the plugin skill a subagent must read: subagents can invoke skills, but they ignore the using-superpowers discipline and will not go looking for them on their own.

````markdown
### Task N: <what changes, in Drupal terms>

**Skills for this task:** drupal-superpowers:drupal-module-development, drupal-superpowers:drupal-testing
**Files:**
- Modify: `<module>/<module>.routing.yml:1-12` (read in this session)
- Modify: `<module>/src/SavedItemsRepository.php:17-45`
- Create: `<module>/src/Controller/FooController.php` (conventions from `<module>/src/Controller/BarController.php`)
- Create: `<module>/tests/src/Kernel/FooTest.php`
**APIs used:** `Drupal\Core\Controller\ControllerBase` (verified in `<docroot>/core/lib/Drupal/Core/Controller/ControllerBase.php`), `Drupal\Core\Cache\CacheableJsonResponse` (NOT VERIFIED — no installed core; confirm before this task)
**Interfaces:**
- Consumes: `saved_items.repository` → `SavedItemsRepository::getSavedNodeIds(AccountInterface $account): int[]` (Task 1)
- Produces: route `saved_items.user_list` (`/user/{user}/saved`), `FooController::page(UserInterface $user): array`

- [ ] **Step 1: Write the failing test** — `<module>/tests/src/Kernel/FooTest.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\<module>\Kernel;

use Drupal\KernelTests\KernelTestBase;
// … the full test class: $modules, installSchema()/installConfig()/installEntitySchema(), the assertions.
```

- [ ] **Step 2: Run it and expect failure**

Run: `<phpunit cmd> -c <phpunit config> <module>/tests/src/Kernel/FooTest.php`
Expected: RED — `<the failing assertion, e.g. Failed asserting that 403 matches expected 200>`

- [ ] **Step 3: Implement the smallest change**

```yaml
# <module>/<module>.routing.yml — requirements and cache-relevant options shown, not described
saved_items.user_list:
  path: '/user/{user}/saved'
  defaults: { _controller: '\Drupal\<module>\Controller\FooController::page', _title_callback: '…' }
  requirements: { _custom_access: '\Drupal\<module>\Access\FooAccess::access' }
  options: { parameters: { user: { type: 'entity:user' } } }
```

```php
// <module>/src/Controller/FooController.php — DI via create(), access-checked loads, cache contexts/tags/max-age in the build.
```

- [ ] **Step 4: Run the test again**

Run: `<phpunit cmd> -c <phpunit config> <module>/tests/src/Kernel/FooTest.php`
Expected: GREEN — `OK (N tests, M assertions)`

- [ ] **Step 5: Neighbours and standards**

Run: `<phpunit cmd> -c <config> <module>/tests` then `<phpcs cmd> <changed files>` and `<phpstan cmd> -c <config> <changed files>`; fix violations, never silence them.

- [ ] **Step 6: Report**

Status DONE | DONE_WITH_CONCERNS | BLOCKED | NEEDS_CONTEXT; one `VERIFY L1|L2 …` line per command; deployment notes (`updb` / `cim` / `cr` / permission grants) if any; then the `git add` / `git commit` lines the user can paste (nothing staged by the implementer).
````
