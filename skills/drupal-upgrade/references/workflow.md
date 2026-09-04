# Upgrade workflow

```
inventory → target version → compatibility matrix → Composer constraints → contrib compatibility
→ deprecations → custom code analysis → automated transformations → manual transformations
→ tests → upgrade execution → runtime verification → report
```

## 1. Inventory (read-only)
- `drupal-profile . --no-cache` : version, PHP, Drush, patches, contrib, custom, tests, CI.
- Per custom module: `grep -rn "^function \|@Block\|@FieldType\|#\[\|extends \|implements \|\\\\Drupal::" <module>`; hooks implemented; services used (`services.yml` arguments); deprecated symbols from `drupal-facts list <current version>`.
- Contrib: `composer show -D --format=json` (through the adapter) for exact versions; patches from `composer.json` `extra.patches`.

## 2. Target
Choose the exact minor (e.g. 11.4.x). From `references/version-jumps.md`: PHP, DB, Symfony, Drush, removed modules, upgrade prerequisites (e.g. 12 requires ≥ 11.4 first). Confirm against drupal.org release notes if the matrix is older than 120 days.

## 3. Compatibility matrix
| Item | Status for target | Evidence | Action |
|---|---|---|---|
| drupal/core | 11.4.6 available | packagist | bump |
| contrib X | 3.0.x supports ^11 | release info.yml | bump |
| contrib Y | no 11 release; issue #NNN RTBC | issue queue | patch via composer-patches or replace |
| custom module Z | 3 deprecations, 1 annotation | phpstan/rector | fix |
| PHP | 8.3 required | matrix | runtime change (hosting) |
| patch P | fails on new core | `composer install` dry run | re-roll or drop |

## 4. Composer
```bash
composer why-not drupal/core-recommended 11.4.6            # what blocks
composer require drupal/core-recommended:^11.4 drupal/core-composer-scaffold:^11.4 drupal/core-dev:^11.4 --update-with-all-dependencies --dry-run
composer outdated drupal/*
```
Do the real `require`/`update` only on LOCAL/DISPOSABLE (bounded to named packages; the guard blocks unbounded `composer update`). Commit lock changes separately from code changes when the project's git workflow allows.

## 5–6. Deprecations and custom code
- `phpstan analyse -c phpstan.neon` with `phpstan-drupal` + `phpstan-deprecation-rules` (`reportUnmatchedIgnoredErrors: false` acceptable during the sweep).
- Upgrade Status (`drush upgrade_status:analyze <module>`) if installed; it also checks info.yml, composer.json, Twig, config keys.
- Rector: `vendor/bin/rector process <module> --dry-run` with `palantirnet/drupal-rector` config; read every diff.
- For each removed symbol: the replacement from the change record (`drupal-lookup <symbol>`), verified in a target-version core checkout (disposable lab or `composer create-project` in a temp dir).

## 7. Classification
Automated: Rector rule exists and the diff is mechanical (function → static method, deprecated service ID, `t()` → `$this->t()`). Manual: signature/behaviour changes, removed core modules (Contact/Telephone/Migrate Drupal in 12), annotation→attribute where the plugin type requires it, theme/Twig changes, config schema changes, test base class changes.

## 8–9. Implement and test
Per module: fix → phpcs → phpstan → module tests on the *current* version (regression) → then on the target (lab). Update `core_version_requirement` last, once tests pass on the target.

## 10–11. Execute and verify
```
composer install (target lock) → drush updb -y (LOCAL) → drush cim (if config changed) → drush cr
→ drush status / core:requirements → key workflows (login, content edit, search, checkout…) → watchdog
```
Each as a `VERIFY` line. On the user's real environments: never run; produce the runbook.

## 12. Report
- APIs changed: `format_size()` → `ByteSizeMarkup::create()` (CR 2999981) …
- Constraints changed, patches changed
- Remaining incompatibilities and who owns them
- Deployment order and rollback (DB dump before updb)
