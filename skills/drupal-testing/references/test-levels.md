# Test levels

| Level | Base class / namespace | Boots | Cost | Proves | Core example |
|---|---|---|---|---|---|
| Unit | `Drupal\Tests\UnitTestCase`, `tests/src/Unit` | nothing | ms | pure logic with injected collaborators | `core/tests/Drupal/Tests/Core/`, `modules/user/tests/src/Unit/` |
| Kernel | `Drupal\KernelTests\KernelTestBase`, `tests/src/Kernel` | container + DB schema you install | ~1 s | services, entity CRUD/access, config, plugins, queries, hooks, cache metadata | `modules/node/tests/src/Kernel/NodeAccessTest.php` |
| Functional | `Drupal\Tests\BrowserTestBase`, `tests/src/Functional` | full site install per test | 10–60 s | routes, permissions, forms, rendered pages, redirects | `modules/node/tests/src/Functional/NodeAccessTest.php` |
| FunctionalJavascript | `Drupal\FunctionalJavascriptTests\WebDriverTestBase`, `tests/src/FunctionalJavascript` | site + WebDriver | 30 s+ | JS, AJAX, behaviors, once() | `modules/system/tests/src/FunctionalJavascript/` |
| Nightwatch | `tests/src/Nightwatch` | site via core's yarn setup | | core JS; rarely used in projects | `core/tests/Drupal/Nightwatch/` |

## Kernel test skeleton

```php
namespace Drupal\Tests\saved_items\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * @group saved_items
 */
final class SavedItemsRepositoryTest extends KernelTestBase {
  use UserCreationTrait;

  protected static $modules = ['system', 'user', 'node', 'field', 'text', 'filter', 'saved_items'];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installSchema('saved_items', ['saved_items']);
    $this->installConfig(['filter', 'node', 'saved_items']);
    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();
  }

  public function testGetSavedNodeIdsExcludesInaccessibleNodes(): void {
    $user = $this->createUser(['access content', 'use saved items']);
    $published = Node::create(['type' => 'article', 'title' => 'a', 'status' => 1]); $published->save();
    $unpublished = Node::create(['type' => 'article', 'title' => 'b', 'status' => 0]); $unpublished->save();
    $repo = $this->container->get('saved_items.repository');
    $repo->save($user, $published); $repo->save($user, $unpublished);
    $this->assertSame([(int) $published->id()], $repo->getSavedNodeIds($user));
  }
}
```
Traits worth knowing: `UserCreationTrait`, `NodeCreationTrait`, `ContentTypeCreationTrait`, `EntityReferenceFieldCreationTrait`, `AssertMailTrait`.

## Functional test skeleton (route + permission)

```php
namespace Drupal\Tests\saved_items\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * @group saved_items
 */
final class SavedItemsRouteTest extends BrowserTestBase {
  protected static $modules = ['node', 'saved_items'];
  protected $defaultTheme = 'stark';

  public function testAccess(): void {
    $this->drupalGet('/api/saved-items');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($this->drupalCreateUser(['access content']));
    $this->drupalGet('/api/saved-items');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($this->drupalCreateUser(['access content', 'use saved items']));
    $this->drupalGet('/api/saved-items');
    $this->assertSession()->statusCodeEquals(200);
  }
}
```
On 11.3+ check the facts registry entry `run-tests-in-separate-processes` (unverified) for required attributes on Functional tests.

## Cache metadata tests (Kernel)

```php
$build = $this->container->get('plugin.manager.block')->createInstance('greeting_block')->build();
$this->assertContains('user', $block->getCacheContexts());
// or render and inspect BubbleableMetadata::createFromRenderArray($build)->getCacheContexts()
```

## Unit test rule of thumb
Allowed when the class takes its collaborators via constructor and the test needs ≤ 2 small mocks/stubs (e.g. `EmailValidatorInterface`). `\Drupal::` calls inside the class make it untestable at unit level: inject instead. `$this->getStringTranslationStub()` for `t()`.
