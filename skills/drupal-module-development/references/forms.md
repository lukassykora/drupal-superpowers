# Forms

## Base classes
| Need | Class | Notes |
|---|---|---|
| Custom form | `FormBase` | `getFormId()`, `buildForm()`, `validateForm()`, `submitForm()` |
| Settings form for simple config | `ConfigFormBase` | `getEditableConfigNames()`; 10.2+: `#config_target` maps elements to config keys and validates via schema constraints |
| Confirmation | `ConfirmFormBase` | `getQuestion()`, `getCancelUrl()` |
| Entity add/edit | `ContentEntityForm` / `EntityForm` | declared in the entity type `handlers.form` |

## Validation

```php
public function validateForm(array &$form, FormStateInterface $form_state): void {
  $email = (string) $form_state->getValue('email');
  if (!$this->emailValidator->isValid($email)) {
    $form_state->setErrorByName('email', $this->t('%email is not a valid e-mail address.', ['%email' => $email]));
  }
  if (mb_strlen(trim((string) $form_state->getValue('note'))) < 10) {
    $form_state->setErrorByName('note', $this->t('The note must be at least @n characters.', ['@n' => 10]));
  }
}
```
- Inject `email.validator` (`EmailValidatorInterface`) or use `'#type' => 'email'` which validates format itself.
- Element-level: `'#element_validate' => [[static::class, 'validateFoo']]`, `#required`, `#maxlength`, `#pattern`.
- Constraints on config forms (10.2+): schema `constraints:` + `#config_target`.
- Errors reference element names with `][` for nested (`setErrorByName('address][postal_code', ...)`).
- Never validate by re-implementing what an element type already checks; never `die()`/`exit`, never print.

## Submission
- `submitForm()` does the work, then `$form_state->setRedirect(...)` or `setRedirectUrl()`; messages via `$this->messenger()`.
- Values: `$form_state->getValue('name')`, `getValues()`; cleaned of buttons/tokens automatically.
- Rebuild for multistep: `$form_state->setRebuild()` + `$form_state->set('step', 2)`; store data in form state, or PrivateTempStore for cross-request wizards.

## AJAX
```php
$form['country'] = ['#type' => 'select', '#options' => ..., '#ajax' => ['callback' => '::updateCities', 'wrapper' => 'cities-wrapper']];
$form['cities'] = ['#type' => 'container', '#attributes' => ['id' => 'cities-wrapper']];
public function updateCities(array &$form, FormStateInterface $form_state): array { return $form['cities']; }
```
- Callbacks return the render array of the wrapper or an `AjaxResponse` with commands.
- AJAX callbacks must not do the work of submit; keep access checks on the form route.
- JS side: `Drupal.behaviors` with `once()` for anything that must re-attach after AJAX.

## Testing
- Kernel: `\Drupal::formBuilder()->submitForm(NoteForm::class, $form_state)` then `$form_state->getErrors()`.
- Functional: `$this->drupalGet('/contact-note'); $this->submitForm([...], 'Send'); $this->assertSession()->pageTextContains(...)`.
- Unit: `validateForm()` with a mocked `FormStateInterface` only for pure validation logic without container calls (inject the validator).

## Security
- Form API adds CSRF tokens for authenticated users; never build POST endpoints outside Form API without `_csrf_token`/`_csrf_request_header_token`.
- Output form values with `#plain_text`, `#markup` through `t()` placeholders, never raw.
- File uploads: `managed_file` with `#upload_validators` (extensions, size), private scheme for non-public files.
