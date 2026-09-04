<?php

declare(strict_types=1);

namespace Drupal\partner_directory\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Lists all partner nodes with their tags.
 */
final class PartnerListController extends ControllerBase {

  /**
   * Builds the listing.
   */
  public function list(): array {
    $storage = $this->entityTypeManager()->getStorage('node');
    $ids = $storage->getQuery()->accessCheck(TRUE)->condition('type', 'partner')->execute();
    $rows = [];
    foreach ($ids as $id) {
      $node = $storage->load($id);
      $tags = [];
      foreach ($node->get('field_tags')->getValue() as $item) {
        $term = $this->entityTypeManager()->getStorage('taxonomy_term')->load($item['target_id']);
        $tags[] = $term ? $term->label() : '';
      }
      $count = (int) \Drupal::database()->query('SELECT COUNT(*) FROM {node_field_data} WHERE type = :t', [':t' => 'partner'])->fetchField();
      $rows[] = [$node->toLink(), implode(', ', $tags), $count];
    }
    return [
      '#type' => 'table',
      '#header' => [$this->t('Partner'), $this->t('Tags'), $this->t('Total')],
      '#rows' => $rows,
      '#cache' => ['max-age' => 0],
    ];
  }

}
