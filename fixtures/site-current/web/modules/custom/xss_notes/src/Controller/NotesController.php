<?php

declare(strict_types=1);

namespace Drupal\xss_notes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Displays a note for a node.
 */
final class NotesController extends ControllerBase {

  /**
   * Shows the note; the "highlight" query parameter is echoed back.
   */
  public function show(NodeInterface $node, Request $request): array {
    $highlight = $request->query->get('highlight', '');
    $build['heading'] = [
      '#markup' => Markup::create('<p class="highlight">' . $highlight . '</p>'),
    ];
    $build['note'] = [
      '#theme' => 'xss_notes_note',
      '#title' => $node->label(),
      '#body' => $node->hasField('body') ? $node->get('body')->value : '',
      '#author' => $node->getOwner()->getDisplayName(),
    ];
    return $build;
  }

}
