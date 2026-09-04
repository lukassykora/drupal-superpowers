<?php

declare(strict_types=1);

namespace Drupal\broken_service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Logs a notification for an entity.
 */
final class Notifier {

  public function __construct(
    private readonly LoggerChannelFactoryInterface $loggerFactory,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Writes a log line for the given node ID.
   */
  public function notify(int $nid): void {
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    $this->loggerFactory->get('broken_service')->notice('Notified about @label', [
      '@label' => $node ? $node->label() : $nid,
    ]);
  }

}
