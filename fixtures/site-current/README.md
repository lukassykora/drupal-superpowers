# Fixture: site-current

Synthetic Drupal 11.4.6 project used by drupal-superpowers evals. No vendor/, no core/.
It exists so that project detection and skill triggering can be tested without a real site.

Custom modules: saved_items (clean), contact_note (form without validation), xss_notes (planted XSS + missing access), greeting_block (planted cache-context bug), broken_service (planted container error), partner_directory (planted N+1 loads and a per-row COUNT query, max-age 0 on a public listing), partner_migrate (migration YAML with planted defects: field_tags not trimmed and not mapped to term IDs, tier empty value, website without scheme, missing dependency on migrate_plus for the group). Custom theme acme (planted frontend defects: `|raw` on rendered body, inline onclick handler instead of Drupal.behaviors, behavior without once(), image without alt, static `\Drupal::service()` in preprocess, SDC component without required prop constraints).

## Local setup

Run `composer install`, then open the site and check the greting on the front page.
