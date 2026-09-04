# Compatibility matrix in disposable labs

Claiming "compatible with 10.6 and 11.4" is only true if the module was installed and tested on both. `scripts/drupal-lab` builds one throw-away site per core constraint and runs the same command in each, so the claim comes with evidence.

## Build the matrix

```bash
"${CLAUDE_PLUGIN_ROOT}/scripts/drupal-lab" matrix saveditems \
  --cores "^10.6,^11.4" \
  --module web/modules/custom/saved_items \
  --command 'vendor/bin/drush pm:enable saved_items -y && vendor/bin/drush cr && vendor/bin/phpunit -c web/core web/modules/custom/saved_items/tests'
```

- One lab per constraint (`saveditems-106`, `saveditems-114`), each marked DISPOSABLE, each with `drupal/core-dev` and Drush.
- The command runs inside each lab with its own resolved runtime; a non-zero exit is reported per core.
- Nothing touches the user's project. Destroy with `drupal-lab destroy <name>` (the script refuses to delete a directory without the lab marker).

## What to run per core

| Check | Command in the lab |
|---|---|
| module installs | `vendor/bin/drush pm:enable <module> -y` |
| container compiles | `vendor/bin/drush cr` |
| schema updates apply | `vendor/bin/drush updatedb:status` then `vendor/bin/drush updb -y` |
| tests | `vendor/bin/phpunit -c web/core web/modules/custom/<module>` (Kernel/Functional; SQLite for native labs, MariaDB for Docker labs) |
| coding standards | `vendor/bin/phpcs --standard=Drupal,DrupalPractice web/modules/custom/<module>` |
| deprecations | `vendor/bin/phpstan analyse -c <config> web/modules/custom/<module>` with phpstan-drupal + deprecation rules, `phpVersion` set to the lab's PHP |
| site boots | `vendor/bin/drush status --fields=bootstrap` and, when the lab serves HTTP, `curl -sI <url>` |

## Reading the result

```
| core | install | cr | phpunit | phpcs | phpstan (deprecations) | verdict |
|---|---|---|---|---|---|---|
| ^10.6 | PASS | PASS | 12 tests, 31 assertions OK | 0 errors | 3 deprecations (format_size…) | fix before claiming 10.6 |
| ^11.4 | PASS | PASS | 12 tests, 31 assertions OK | 0 errors | 0 | compatible |
```

Only after every row is green does `core_version_requirement: ^10.6 || ^11.4` become a supported claim; otherwise narrow the constraint. Record the table in the upgrade report (`references/report-template.md`, table 3) with the exact commands as `VERIFY` lines.

## Cost and hygiene

A lab is 200–300 MB and takes 3–15 minutes to build (Docker labs install PHP extensions on first start). Build them only for real compatibility questions, reuse one per core within a session, and destroy them when the answer is recorded. `drupal-lab list` shows what is still on disk.
