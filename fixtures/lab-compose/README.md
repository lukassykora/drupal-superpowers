# Disposable lab (Docker Compose)

Minimal PHP CLI + MariaDB stack for a throw-away Drupal install when DDEV is not available. The PHP
image installs the needed extensions on first start (slow the first time; use a prebuilt image via
`DSP_PHP_IMAGE` for repeated use). The `web` service uses PHP's built-in server with Drupal's
`.ht.router.php`, enough for Functional tests and manual checks, not for performance work.

The lab recipe (`skills/drupal-runtime-verification/references/disposable-lab.md`) copies this directory,
writes the `.drupal-superpowers-lab` marker, and runs Composer/Drush through `docker compose exec php`.
Nothing here touches the user's project.
