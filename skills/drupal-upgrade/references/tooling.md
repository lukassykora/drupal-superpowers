# Upgrade tooling

| Tool | Package / version (2026-09) | Use | Output is |
|---|---|---|---|
| Upgrade Status | `drupal/upgrade_status` 5.x (core ^10.4 ‖ ^11 ‖ ^12) | `drush upgrade_status:analyze <module>` or the UI at `/admin/reports/upgrade-status`; checks deprecated PHP APIs (via phpstan-drupal), Twig, config keys, info.yml/composer.json, environment requirements | findings to classify |
| phpstan-drupal + deprecation rules | `mglaman/phpstan-drupal` 2.1.x, `phpstan/phpstan-deprecation-rules` 2.x | `phpstan analyse -c phpstan.neon` with `extension.neon` + `rules.neon` + deprecation `rules.neon`; set `phpVersion` for the target | precise file:line deprecations |
| Drupal Rector | `palantirnet/drupal-rector` 1.1.x (Rector ^2) | `vendor/bin/rector process <path> --dry-run --config vendor/palantirnet/drupal-rector/rector.php` (copy the config to the project to customize); rules for 10.0 → 11.4 deprecations, hook → OOP, BC-wrapping | diffs to review |
| Composer | `composer why-not`, `composer outdated`, `--dry-run`, `composer audit` | constraint resolution | |
| Drush | `drush pm:security`, `drush pm:security-php`, `drush core:requirements`, `drush updatedb:status` | site-side checks | |
| Change records | `drupal-lookup <symbol>`; drupal.org list filtered by target branch | replacement APIs and reasons | citations |
| Drupal Code Query | `POST https://api.tresbien.tech/v1/composer/scan` with the lock file → readiness for the next major; `/v1/symbol/{id}` lifecycle | quick readiness view | third-party, optional |

Not recommended (dead/legacy): `mglaman/phpstan-drupal-deprecations` (2019), `drupal-check` (phpstan-drupal 1.x), Drupal Console (EOL), `drupal_upgrade`/`dru` ad-hoc scripts.

## Reading Rector output
1. Each hunk: which rule, which change record? If the rule's replacement does not exist in the target (contrib plugin type without attribute class), reject the hunk.
2. Hunks that change behaviour (e.g. `$this->t()` in static context, entity query `accessCheck(FALSE)` insertions) need a test.
3. Never apply `--no-diffs` blindly; run tests after each module.

## Common 10 → 11 items
| Symbol | Replacement | Note |
|---|---|---|
| `format_size()` | `\Drupal\Core\StringTranslation\ByteSizeMarkup::create()` | CR 2999981 |
| `watchdog_exception()` | `\Drupal\Core\Utility\Error::logException($logger, $e)` | CR 2932520 |
| `system_time_zones()` | `\Drupal\Core\Datetime\TimeZoneFormHelper::getOptionsList()` etc. | CR 3023528 |
| annotation plugins (core types) | attribute classes (`#[Block]`, …) | CR 3395582; required for plugin types in 12.0 |
| `Drupal\Core\Entity\EntityTypeManagerInterface::getFormObject()`? | verify in core | example of "check, don't assume" |
| PHPUnit 9 annotations | PHPUnit 10/11 attributes | `#[Group]`, static data providers |
| Symfony 6 → 7 signatures | typed returns/params in event subscribers, `Request::get()` removal | phpstan finds them |
Verify each in the target core before applying; the registry `drupal-facts list <target>` shows which apply.
