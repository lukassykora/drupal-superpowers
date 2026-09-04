---
name: drupal-module-development
description: Use when creating or editing a custom Drupal module: *.info.yml, *.services.yml, *.routing.yml, *.permissions.yml, *.links.*.yml, *.libraries.yml, config/install, config/schema, controllers, forms, plugins, entity types, hook implementations, update or post-update hooks.
paths:
  - "**/modules/custom/**"
  - "**/*.info.yml"
  - "**/*.services.yml"
  - "**/*.routing.yml"
  - "**/*.permissions.yml"
  - "**/*.module"
  - "**/*.install"
---

# Drupal module development

**Core principle:** only the files the feature needs, each in the shape core uses for that file in this Drupal version, with access and cache metadata designed in, not bolted on.

## When to use

Creating a module, adding a route/controller/form/service/plugin/entity/permission/hook, or changing any of the YAML files above. Not for theme work (`drupal-frontend`) or for pure config changes without code (`drupal-config`).

## Procedure

1. **Profile and neighbours.** Version class and custom path from `drupal-project-understanding`. Open the nearest existing module and copy its conventions: DI style (`create()` vs autowire), hook style, test layout, `declare(strict_types=1)` usage, docblock style.
2. **Verify every API you will call** that you cannot point to in this project's core (`drupal-research`). Attributes vs annotations, `#[Hook]` availability, and deprecations are version facts, not memory.
3. **Design the access and cache model before code** (`drupal-security`, `drupal-cacheability`): which permission or entity access guards the route; what the output varies by.
4. **Write the test first** (`drupal-testing`): Kernel for services/entities/config, Functional for routes + permissions. Expect RED.
5. **Create files** from the per-file rules in the references, smallest set first:
   - [references/config-files.md](references/config-files.md): `.info.yml`, `.permissions.yml`, `.links.*.yml`, `.libraries.yml`, `config/install` + `config/schema`
   - [references/services.md](references/services.md): `.services.yml`, DI, autowiring, decorators, event subscribers
   - [references/routing.md](references/routing.md): routes, parameters, access requirements, controllers, JSON responses
   - [references/forms.md](references/forms.md): FormBase/ConfigFormBase, validation, AJAX, multistep
   - [references/plugins.md](references/plugins.md): attributes, annotations, plugin managers, derivatives, blocks, field/queue workers
   - [references/entities.md](references/entities.md): content and config entity types, access handlers, storage, queries, revisions, translations
   - [references/hooks.md](references/hooks.md): procedural vs `#[Hook]`, alter hooks, install/update/post-update hooks
6. **Run L1** on the changed files (phpcs, phpstan), then the test (GREEN), then neighbours (`drupal-runtime-verification`).
7. **Report** with the ledger and deployment notes (new module → enable; schema → `updb`; config → export).

## Rules that apply to every file

- DI in classes; `\Drupal::` only in procedural code (`.module`, `.install`, `.theme` hooks) and where the API expects it (e.g. static `create()` bodies).
- `declare(strict_types=1);` and typed properties/returns when the project's own modules use them; match the project otherwise.
- Every route with data: `_permission`, `_entity_access`, `_custom_access`, or `_role`; never `_access: 'TRUE'` unless the content is public by design and you say so.
- Every render array/response: cache metadata. Every user-facing string: `t()`/`TranslatableMarkup` with placeholders, never concatenated HTML.
- `core_version_requirement` matches the project's major (and the next one only when tested).
- No boilerplate "for later": no empty `.module`, no unused `libraries.yml`, no `config/install` without schema.
- Language: all code, machine names (modules, fields, config keys, routes, services, permissions), identifiers, comments, docblocks, YAML labels/descriptions, test names, commit-ready text, and reports are written in English, whatever language the conversation uses. User-facing strings are English inside t()/TranslatableMarkup/{% trans %} so translations come from Drupal's translation system, never hard-coded in another language.
- Update hooks: schema changes in `hook_update_N`, data/config changes in `hook_post_update_NAME`; both idempotent.

## Works with process skills

Inside `superpowers:subagent-driven-development` implementers see only their brief: put the file rules that matter into the task (or name this skill in the brief). With `superpowers:test-driven-development`, steps 4–6 are its RED/GREEN cycle with Drupal test levels.

## Red flags

| Thought | Reality |
|---|---|
| "Scaffold the full module skeleton" | Unused files are review noise and a maintenance cost; add files when a feature needs them. |
| "`_access: 'TRUE'` for now" | It ships. Decide the permission now. |
| "I'll use `\Drupal::entityTypeManager()` in the controller" | Inject it; the reviewer will flag it and tests get harder. |
| "Annotations still work" | In this version? Check `drupal-facts check plugin-attributes <version>` and the plugin type's manager. |
| "Config change, no schema needed" | Missing schema breaks config validation and translation; add it. |
