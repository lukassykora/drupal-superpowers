# Drupal Global Constraints (plan template)

Fill every `<...>` from `drupal-profile` / `drupal-runtime` output. Copy verbatim into the plan's Global Constraints and into each implementer brief and reviewer `[GLOBAL_CONSTRAINTS]` slot.

```
## Global Constraints (Drupal)
- Drupal <version> (<class>; source: composer.lock). PHP <constraint>. Drush <version>. Only APIs present in this version; verify unfamiliar ones in the installed core (`<docroot>/core`) or with `drupal-lookup`.
- Runtime: <adapter> (<environment class>). Commands: drush = `<cmd>`, phpunit = `<cmd>`, phpcs = `<cmd>`, phpstan = `<cmd>`. Project wrappers: `<composer test / make lint ...>` take precedence.
- Code goes under `<custom module path>/<module>`; follow the conventions of neighbouring modules.
- Coding standards: `<phpcs cmd> --standard=<project ruleset | Drupal,DrupalPractice>`; static analysis: `<phpstan cmd> -c <config>`. Both must pass on changed files.
- Dependency injection in classes (controllers, forms, plugins, services, event subscribers); `\Drupal::` only in procedural code (`.module`, `.install`, `.theme` hooks) and where the API expects it.
- Access: every route declares `_permission`, `_entity_access`, `_custom_access`, or a role requirement; never `_access: 'TRUE'` on data-bearing routes. Entities loaded for output are access-checked (`->access('view')`, entity query `accessCheck(TRUE)`).
- Cacheability: render arrays and responses declare tags, contexts (`user`, `user.permissions`, `url.query_args`, `languages:language_interface` as applicable), and max-age. Personalized output never ships without a context.
- Configuration: every config/install YAML has schema; environment-specific values go to settings.php/overrides, not config; `drush cim -y` is never run by the implementer.
- Tests: <level> tests under `tests/src/<Level>/`; bugfixes start with a failing regression test; test command `<cmd>`; no assertions removed or weakened to pass.
- Deployment: changes needing `drush updb`, `drush cim`, cache rebuild, or reindexing are listed in the task report.
- Verification report format: `VERIFY L1|L2|L3 <check> PASS|FAIL|NOT VERIFIED — <reason>`; no completion claim without it.
- Git: no commits, pushes, resets by agents unless the user asked for exactly that.
- Language: all code, machine names (modules, fields, config keys, routes, services, permissions), identifiers, comments, docblocks, YAML labels/descriptions, test names, commit-ready text, and reports are written in English, whatever language the conversation uses. User-facing strings are English inside t()/TranslatableMarkup/{% trans %} so translations come from Drupal's translation system, never hard-coded in another language.
```
