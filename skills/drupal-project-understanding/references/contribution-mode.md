# Contribution mode (project_kind = contrib-module | core)

When the profile says the working tree is a drupal.org contrib module or Drupal core itself, site assumptions do not hold and drupal.org conventions do.

## contrib-module (composer.json `name: drupal/<x>`, `.gitlab-ci.yml` from the templates, `<x>.info.yml` at the root)
- **No site**: there is no `config/sync`, no settings, no runtime. Verification runs in a disposable site (`drupal-runtime-verification` lab) or via the module's own `phpunit` setup; state `NOT VERIFIED` otherwise.
- **Compatibility is a contract**: `core_version_requirement` covers every branch you claim; test on the lowest and highest; PHP floor from `composer.json`; no APIs newer than the floor without a BC shim (`#[LegacyHook]`, `class_exists` guards).
- **GitLab CI**: the drupal.org template (`include: project: $_GITLAB_TEMPLATES_REPO`) runs phpcs, phpstan, phpunit across matrix variants (`OPT_IN_TEST_PREVIOUS_MAJOR`, `_NEXT_MINOR`, `_MAX_PHP`); mirror those checks locally.
- **Conventions**: coding standards strict (phpcs `Drupal,DrupalPractice` with `--extensions=php,module,inc,install,test,profile,theme,css,info,txt,md,yml`), `README.md` per the drupal.org template, `composer.json` with `type: drupal-module` and `license: GPL-2.0-or-later`, semantic versioning branches (`1.x`, `2.0.x`), change records for your own API breaks, `hook_update_N` numbering per branch, deprecations via `@deprecated in x:1.2.0 and is removed from x:2.0.0` + `trigger_error`.
- **Issues**: work is tied to a drupal.org issue (fork branch `<nid>-short-description`, merge request), commit messages `Issue #<nid> by <authors>: <title>`; do not create commits unless asked. Tests are required for bug fixes (test-only commit showing the failure first is the norm).
- **Security**: never disclose vulnerabilities in public issues; the security team process applies. Contributed modules follow the same escaping/access rules as core.
- **Skills from the module**: read the module's own `.agents/skills/` if present.

## core (core/lib/Drupal.php at the root of the checkout)
- Branch policy: development on `main` (12.x), bug fixes backported to supported minors; feature freeze/API freeze dates from the release schedule; `@internal` classes are not API.
- Tests: `core/phpunit.xml.dist`, `core/scripts/run-tests.sh --sqlite ... --class`, Nightwatch (`core/tests/Drupal/Nightwatch`), `core/scripts/dev/commit-code-check.sh` (phpcs, phpstan, eslint, stylelint, spellcheck) before proposing a change.
- Conventions: change record for any API change; `@deprecated`/`@see` with change-record URL and `trigger_error`; BC layers for deprecations; test coverage mandatory; issue metadata (component, priority, "Needs tests"/"Needs change record" tags).
- Never edit `vendor/`; core's own `composer.json` constraints define platform requirements.

## custom-module inside a site
Site rules apply (config sync, runtime), plus the module's `core_version_requirement` must match the site's core; tests live in `tests/src/`; no drupal.org packaging needed.
