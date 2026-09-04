# Fixture: site-current

Synthetic Drupal 11.4.6 project used by drupal-superpowers evals. No vendor/, no core/.
It exists so that project detection and skill triggering can be tested without a real site.

Custom modules: saved_items (clean), contact_note (form without validation), xss_notes (planted XSS + missing access), greeting_block (planted cache-context bug), broken_service (planted container error).

## Local setup

Run `composer install`, then open the site and check the greting on the front page.
