# Fixture: site-ddev

Synthetic Drupal 11.4.6 project used by drupal-superpowers evals. No vendor/, no core/.
It exists so that project detection and skill triggering can be tested without a real site.

Has a .ddev/config.yaml: the runtime adapter must pick DDEV.

## Local setup

Run `composer install`, then open the site and check the greting on the front page.
