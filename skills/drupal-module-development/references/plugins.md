# Plugins

## Discovery: attributes vs annotations (version fact)
- Attributes for core plugin types since 10.2 (`drupal-facts check plugin-attributes <version>`); attribute class required for every plugin *type* from 12.0; annotation support removed in 13.
- A contrib plugin type may still discover by annotation only: check its manager (`AttributeDiscoveryWithAnnotations` vs `AnnotatedClassDiscovery`) before writing an attribute.
- Never mix both on one class unless supporting ≤ 10.1 and ≥ 10.2 simultaneously (then attribute + annotation is the documented BC pattern).

## Block

```php
#[Block(id: 'greeting_block', admin_label: new TranslatableMarkup('Greeting'), category: new TranslatableMarkup('Custom'))]
final class GreetingBlock extends BlockBase implements ContainerFactoryPluginInterface {
  public function __construct(array $configuration, string $plugin_id, mixed $plugin_definition, private readonly AccountProxyInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('current_user'));
  }
  public function build(): array {
    return ['#markup' => $this->t('Welcome back, @name!', ['@name' => $this->currentUser->getDisplayName()])];
  }
  public function getCacheContexts(): array { return Cache::mergeContexts(parent::getCacheContexts(), ['user']); }
  public function getCacheTags(): array { return Cache::mergeTags(parent::getCacheTags(), ['user:' . $this->currentUser->id()]); }
  protected function blockAccess(AccountInterface $account): AccessResult { return AccessResult::allowedIfHasPermission($account, 'access content'); }
  public function defaultConfiguration(): array { return ['greeting' => 'Welcome back']; }
  public function blockForm($form, FormStateInterface $form_state): array { /* config form */ return $form; }
}
```
Core examples: `modules/user/src/Plugin/Block/UserLoginBlock.php`, `modules/system/src/Plugin/Block/SystemMenuBlock.php`. Block config needs schema: `block.settings.greeting_block:` in `config/schema`.

## Other common plugin types
| Type | Attribute | Base class | Core example |
|---|---|---|---|
| Field type / widget / formatter | `#[FieldType]`, `#[FieldWidget]`, `#[FieldFormatter]` | `FieldItemBase`, `WidgetBase`, `FormatterBase` | `modules/text/src/Plugin/Field/*` |
| Queue worker | `#[QueueWorker(id:, title:, cron: ['time' => 60])]` | `QueueWorkerBase` | `modules/locale/src/Plugin/QueueWorker/LocaleTranslation.php` |
| Condition | `#[Condition]` | `ConditionPluginBase` | `modules/user/src/Plugin/Condition/UserRole.php` |
| Action | `#[Action]` | `ActionBase`/`ConfigurableActionBase` | `modules/node/src/Plugin/Action/` |
| Views plugins | `#[ViewsField]` etc. | `FieldPluginBase` ... | `modules/views/src/Plugin/views/` |
| Migrate source/process/destination | `#[MigrateSource]`, `#[MigrateProcess]`, `#[MigrateDestination]` | `SourcePluginBase`, `ProcessPluginBase`, `DestinationBase` | `modules/migrate/src/Plugin/migrate/` |
| REST resource | `#[RestResource]` | `ResourceBase` | `modules/rest/src/Plugin/rest/resource/` |
| Menu link, local task/action (derivatives) | YAML + deriver class | `DeriverBase` | `modules/system/src/Plugin/Derivative/SystemMenuBlock.php` |

## Defining a plugin type (only with ≥ 2 real implementations)
1. Interface (`src/FooInterface.php`) and base class.
2. Attribute class `src/Attribute/Foo.php` extending `Plugin` (+ annotation class only for ≤ 10.1 support).
3. Manager `src/FooPluginManager.php` extending `DefaultPluginManager` with subdir `Plugin/Foo`, interface, attribute (and annotation) classes; `alterInfo('foo_info')`, `setCacheBackend($cache_backend, 'foo_plugins')`.
4. Service `plugin.manager.foo` with `parent: default_plugin_manager`.
5. Kernel test that discovers the plugin.

## Derivatives and contexts
- Derivatives: `deriver: Drupal\m\Plugin\Derivative\FooDeriver`; IDs become `base:derivative`.
- Context-aware plugins (blocks needing a node): `context_definitions` in the attribute, `ContextAwarePluginInterface`; access and cacheability must include the context's cacheability.

## Verification
- `drush cr` after adding plugins (discovery cache); `drush php:eval "print_r(array_keys(\Drupal::service('plugin.manager.block')->getDefinitions()));"` (read-only) to confirm discovery.
