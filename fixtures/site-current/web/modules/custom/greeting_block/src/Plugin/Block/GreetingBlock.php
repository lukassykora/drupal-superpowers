<?php

declare(strict_types=1);

namespace Drupal\greeting_block\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Shows a personal greeting.
 */
#[Block(
  id: 'greeting_block',
  admin_label: new TranslatableMarkup('Greeting'),
)]
final class GreetingBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    private readonly AccountProxyInterface $currentUser,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('current_user'));
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $name = $this->currentUser->isAuthenticated()
      ? $this->currentUser->getDisplayName()
      : $this->t('guest');
    return [
      '#markup' => $this->t('Welcome back, @name!', ['@name' => $name]),
    ];
  }

}
