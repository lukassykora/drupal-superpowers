<?php

namespace Drupal\legacy_tools\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a legacy block.
 *
 * @Block(
 *   id = "legacy_tools_block",
 *   admin_label = @Translation("Legacy block"),
 * )
 */
class LegacyBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => t('Upload limit: @limit', ['@limit' => legacy_tools_upload_limit()]),
    ];
  }

}
