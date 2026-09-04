---
name: drupal-config
description: Use when working with Drupal configuration: config/install, config/optional, config/schema, config sync directory, config split, overrides in settings.php, config drift, drush cex or cim, or deciding between config, state, tempstore, and content.
paths:
  - "**/config/**"
  - "**/settings*.php"
---

# Drupal configuration

**Core principle:** configuration is code that deploys; everything that must not deploy (runtime values, per-environment settings, secrets, user data) lives elsewhere. Drift between the sync directory and the site is a finding to understand, never something to overwrite with `cim -y`.

## When to use

Adding or changing config a module ships, exporting/importing site config, diagnosing drift or import failures, choosing where a value belongs. Not for entity data modelling (`drupal-architecture`/`drupal-module-development`).

## Procedure

1. **Classify the value** with [references/storage-decision.md](references/storage-decision.md): config (deploys, schema-validated, translatable), state (runtime, per environment), tempstore (per user/session), content (entities), settings.php (environment/secrets), key/value or cache (rebuildable).
2. **Module-shipped config**: `config/install/<name>.yml` + `config/schema/<module>.schema.yml` in the same change ([references/schema.md](references/schema.md)); `config/optional` when it depends on modules that may be absent; `dependencies:` (module/config/enforced) declared so uninstall works. Install-time only: existing sites need a `hook_post_update_NAME` or an export from the site.
3. **Site config**: the profile's `paths.config_sync`; export with the project's command (`drush cex` through the adapter) after UI/API changes; commit the YAML; never hand-edit exported YAML for values you could set via the API unless the project does so deliberately.
4. **Drift and import** ([references/drift.md](references/drift.md)): `drush config:status`, `drush cim --diff --no` (Drush 12+; `--no` aborts at the prompt); read the diff; decide per item: export (site is right), import (repo is right), split (environment-specific), or fix code. `drush cim -y` is blocked by the guard everywhere except DISPOSABLE; on LOCAL, review the diff (`cim --diff --no`) and let the user run the import, or ask them to approve it explicitly.
5. **Environment-specific values**: `$config['system.logging']['error_level']` overrides in `settings.<env>.php`, or Config Split (profile field `paths.config_split`); overrides never appear in exported config and are invisible in the UI by design.
6. **Deployment** ([references/deployment.md](references/deployment.md)): the order this project uses (`deployment.hints` in the profile: typically `composer install` → `drush updb` → `drush cim` → `drush cr`, or `drush deploy`); list what your change needs.
7. Record `VERIFY` lines: schema validated (kernel test, or `drush config:inspect` when the contrib `config_inspector` module is installed), `config:status` clean or explained, import diff reviewed.

## Decision rules

- A value set once per environment by an admin and never deployed → state or settings.php override; not config.
- A value an editor changes in production that must survive the next deploy → content or state, or config only if the deploy process exports before import.
- Missing schema is a defect (validation, translation, typed config), not a warning to silence with `$strictConfigSchema = FALSE`.
- Config entities: `config_export` list and schema mapping must match; third-party settings need their own schema.
- Multilingual sites: config translation lives in `language/<langcode>/` inside the sync directory. Machine names, config keys, and default labels are English; other languages go through config translation, not into the default YAML.

## Works with process skills

Design-time: fills the configuration row of `drupal-architecture`'s review. Debugging: the "config schema / drift" family. Verification: the deployment-notes gate in `drupal-verification`.

## Red flags

| Thought | Reality |
|---|---|
| "Run `drush cim -y` to fix the drift" | The diff may contain an editor's production changes; read it first. |
| "Store the API token in config" | Config is exported to git; use settings.php from the environment or Key. |
| "Config schema is optional" | Kernel tests fail on missing schema; config validation and translation need it. |
| "I'll edit the YAML in config/sync directly" | Fine for review, but the source of truth is the site until exported; keep them consistent. |
| "State is like config without export" | State is per environment and not versioned; do not put deployable settings there. |
