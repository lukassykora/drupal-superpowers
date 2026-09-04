---
name: drupal-project-understanding
description: Use when starting non-trivial work in a Drupal codebase (composer.json with drupal/core, web/ or docroot/, *.info.yml) or when Drupal version, PHP version, docroot, custom module paths, config directory, test or lint commands, or local runtime (DDEV, Lando, Docker, none) are unknown or assumed.
user-invocable: true
argument-hint: "[path]"
---

# Drupal project understanding

**Core principle:** every fact about the project comes from a file or a command output you can quote. Version, docroot, paths, tooling, and runtime are never assumed.

## When to use

- First non-trivial task in a Drupal repository, or a new session without the SessionStart brief.
- Any time you are about to write "this project uses …" without having read the file that proves it.
- Before choosing an API (needs the version), a command (needs the runtime), or a test level (needs the tooling).

When not to use: pure documentation edits, or a follow-up task in a session where the profile was already produced and nothing in `composer.lock`, `.ddev/`, or `.lando.yml` changed.

## Procedure

1. Run the profile and the runtime resolver (both static, safe anywhere):

   ```bash
   "${CLAUDE_PLUGIN_ROOT}/scripts/drupal-profile" . --summary
   "${CLAUDE_PLUGIN_ROOT}/scripts/drupal-runtime" . --summary
   ```

   Use `--no-cache` if you changed dependencies in this session; drop `--summary` for the full JSON (field list in [references/profile-fields.md](references/profile-fields.md)).
2. Read the router line. `current` → modern APIs; `previous` → only APIs present in that minor (check with `drupal-facts check <id> <version>`); `eol` → legacy mode, say so once, do not modernize unrequested; `dev` → treat facts as unverified until confirmed in installed core.
3. Fill the gaps the scripts cannot see by reading, not guessing: project `CLAUDE.md`/`README`, the CI file, `composer.json` scripts, `.ddev/config.yaml`, `settings*.php` (never print secrets).
4. If the task needs runtime facts (enabled modules, entity types, fields, config drift), get them from the site through the resolved `drush` command with `--format=json`, or read-only MCP tools if present (see `drupal-runtime-verification`). Never from memory.
5. State the profile in five lines or fewer before continuing: version + class, runtime + environment class, custom code paths, test/lint commands, notable features (multilingual, moderation, config split, patches).

## Decision rules

- **`project_kind`** changes the rules: `site` → site conventions and config sync matter; `custom-module`/`contrib-module` → no site assumptions, `core_version_requirement` and drupal.org conventions matter; `core` → core contribution workflow. Details for contrib and core work: [references/contribution-mode.md](references/contribution-mode.md).
- **`environment.class` other than LOCAL/DISPOSABLE** → every state-changing command needs the user's explicit approval for that environment (the guard hook enforces the destructive subset).
- **`adapter: none`** → static and unit-level evidence only; say `NOT VERIFIED` for runtime claims and offer a disposable lab.
- **Project tooling beats plugin defaults**: if `composer test`, a Makefile target, or `.gitlab-ci.yml` defines the commands, use those.
- Project `.agents/skills/drupal-*` (ai_best_practices) present → read the matching skill for that topic before applying this plugin's references.

## Works with process skills

With `superpowers:brainstorming` active, run this during its "explore project context" step and feed the five-line profile into the design. With `superpowers:writing-plans`, copy the resolved commands into the plan's Global Constraints (template in `drupal-workflow`). Standalone, this is the first phase of `drupal-workflow`.

## Red flags

| Thought | Reality |
|---|---|
| "It's a standard Drupal 10 project" | The lock file says which minor; 10.6 and 10.2 differ in available APIs. |
| "Custom modules are in web/modules/custom" | `docroot/` and `html/` are common; the profile prints the real path. |
| "I'll just run drush cr" | Which drush? The adapter says `ddev drush`, `lando drush`, `vendor/bin/drush`, or none. |
| "There's no test setup" | Look for `phpunit.xml*`, `core-dev`, composer scripts, CI jobs before concluding. |
