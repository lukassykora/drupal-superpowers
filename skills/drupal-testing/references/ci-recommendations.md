# CI recommendations

Only propose CI changes when the user asks or when a gate the project already declares is missing. Read the existing pipeline first: a project with GitLab CI on drupal.org needs different advice from a site project on GitHub Actions.

## What a Drupal pipeline should gate

| Stage | Command | Blocking? |
|---|---|---|
| composer validate | `composer validate --no-check-all` | yes |
| coding standards | `vendor/bin/phpcs --standard=<project ruleset or Drupal,DrupalPractice> <custom paths>` | yes |
| static analysis | `vendor/bin/phpstan analyse -c <config>` (phpstan-drupal + deprecation rules) | yes |
| unit + kernel tests | `vendor/bin/phpunit -c web/core --testsuite unit,kernel <custom paths>` with `SIMPLETEST_DB` | yes |
| functional tests | same with `SIMPLETEST_BASE_URL`, a web server and `BROWSERTEST_OUTPUT_DIRECTORY` | yes, nightly if slow |
| functional JS | plus chromedriver/selenium and `MINK_DRIVER_ARGS_WEBDRIVER` | nightly |
| dependency audit | `composer audit`, `drush pm:security` on an installed site | report, not blocking |
| config drift | `drush config:status` after `drush cim` on a fresh install | yes for site projects |
| front-end | project's ESLint/Stylelint/build | yes when the theme has a build |

## Site projects (GitHub Actions / GitLab CI)

- Cache `~/.composer/cache` and `vendor/`; install with `composer install --no-interaction --no-progress`.
- Database service (MariaDB/MySQL/PostgreSQL) for kernel and functional tests; SQLite is enough for kernel-only pipelines.
- Install the site once per job from the exported config (`drush site:install --existing-config`) to catch config that does not import.
- Run `drush updatedb:status` on the deployed database dump when one is available; a pending update that fails in CI is cheaper than one that fails on deploy.
- Deploy job mirrors the project's own order (`composer install` → `drush updb` → `drush cim` → `drush cr`), never invents one.

## Contrib modules on drupal.org (GitLab CI)

Use the maintained template instead of hand-written jobs:

```yaml
include:
  - project: $_GITLAB_TEMPLATES_REPO
    ref: $_GITLAB_TEMPLATES_REF
    file: /includes/include.drupalci.main.yml
variables:
  OPT_IN_TEST_PREVIOUS_MAJOR: 1   # test on the previous supported core major
  OPT_IN_TEST_NEXT_MINOR: 1       # catch deprecations early
  OPT_IN_TEST_MAX_PHP: 1
```
It provides phpcs, phpstan, phpunit and the core-version matrix; mirror the same commands locally so failures reproduce.

## Rules

- Never add a CI file to a project that has none without asking; propose it and show the file.
- Never weaken an existing gate (lowering the phpstan level, excluding a directory) to make a pipeline green; report the failure instead.
- Keep the local commands and the CI commands identical, so `scripts/drupal-runtime` output and CI agree.
- Long suites (functional, functional-js, matrix) belong in nightly or label-triggered jobs, not on every push, and the pipeline must say what it skipped.
