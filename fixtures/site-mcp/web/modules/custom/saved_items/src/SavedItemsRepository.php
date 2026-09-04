<?php

declare(strict_types=1);

namespace Drupal\saved_items;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Stores and reads the per-user list of saved nodes.
 */
final class SavedItemsRepository {

  public function __construct(
    private readonly Connection $database,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Saves a node for the given account (idempotent).
   */
  public function save(AccountInterface $account, NodeInterface $node): void {
    $this->database->merge('saved_items')
      ->keys(['uid' => $account->id(), 'nid' => $node->id()])
      ->fields(['created' => \Drupal::time()->getRequestTime()])
      ->execute();
  }

  /**
   * Returns the node IDs saved by the account, newest first.
   *
   * @return int[]
   *   Node IDs.
   */
  public function getSavedNodeIds(AccountInterface $account): array {
    $ids = $this->database->select('saved_items', 's')
      ->fields('s', ['nid'])
      ->condition('uid', $account->id())
      ->orderBy('created', 'DESC')
      ->execute()
      ->fetchCol();
    return array_map('intval', $ids);
  }

}
