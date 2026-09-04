<?php

declare(strict_types=1);

namespace Drupal\contact_note\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Collects a short note and an e-mail address.
 */
final class NoteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'contact_note_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your e-mail'),
      '#required' => TRUE,
    ];
    $form['note'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Note'),
      '#required' => TRUE,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->messenger()->addStatus($this->t('Thanks, your note was recorded.'));
  }

}
