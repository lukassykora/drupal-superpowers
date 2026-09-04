# Disposable Drupal lab

An isolated, throw-away Drupal environment for module development, bug reproduction, API experiments, upgrade trials, or browser verification when the project has no runnable environment. **Offered, never created automatically.** Suggested wording:

> Runtime verification is NOT VERIFIED here (no DDEV/Lando/Docker runtime, no vendor/). I can create a disposable DDEV/Docker Drupal environment under the plugin data directory to run the tests and the site for real. Want me to?

## Rules

- Never install Docker or DDEV, change host configuration, or touch the user's project environment.
- Create the lab outside the project (`${CLAUDE_PLUGIN_DATA}/labs/<name>` or a git worktree) and write the marker file `.drupal-superpowers-lab` at its root; the runtime adapter then classifies it DISPOSABLE and the guard allows destructive commands there.
- Match the project's Drupal major/minor and PHP from the profile; use the project's `composer.json` constraints when the lab exists to test the project's own module.
- Teardown is one command; say it when the lab is created.

## Recipe: DDEV (preferred when `ddev` exists)

```bash
LAB="${CLAUDE_PLUGIN_DATA:-$TMPDIR}/labs/<name>" && mkdir -p "$LAB" && cd "$LAB"
composer create-project drupal/recommended-project:^<major.minor> . --no-interaction
composer require --dev drupal/core-dev:^<major.minor> --with-all-dependencies && composer require drush/drush:^13
ddev config --project-type=drupal --docroot=web --php-version=<php> --project-name=dsp-<name>
touch .drupal-superpowers-lab
ddev start
ddev drush site:install minimal -y --account-pass=admin
# module under test: symlink or copy into web/modules/custom/<module>, then
ddev drush pm:enable <module> -y && ddev drush cr
ddev exec vendor/bin/phpunit -c web/core web/modules/custom/<module>
```
Teardown: `ddev delete -Oy dsp-<name> && rm -rf "$LAB"`.

## Recipe: Docker Compose (when only `docker` exists)

Use `fixtures/lab-compose/` from the plugin (php + mariadb) copied into the lab directory; `docker compose up -d` (set `DSP_WEB_PORT` to a free host port; default 8480), then the same Composer/Drush steps via `docker compose exec php ...`. Teardown: `docker compose down -v && rm -rf "$LAB"`.

## What the lab proves

- L2: module installs, container compiles, tests run against a real database.
- L3: HTTP/browser flows on `https://dsp-<name>.ddev.site` (or the compose port).

It does not prove behaviour on the user's real site (different modules, config, data); say so in the report.
