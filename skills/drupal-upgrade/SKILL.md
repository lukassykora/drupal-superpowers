---
name: drupal-upgrade
description: Use when upgrading Drupal core major or minor versions, removing deprecated Drupal APIs, handling Drupal Rector or Upgrade Status output, PHP or Symfony compatibility for Drupal, or bumping a contrib module's major version; not for content updates or routine composer update.
user-invocable: true
argument-hint: "[module or target version]"
model: opus
effort: high
---

# Drupal upgrade

**Core principle:** an upgrade is an inventory, a target, a compatibility matrix, and then changes, each proven by a check that runs against the target. Automated transformation output (Rector) is input to review and tests, never evidence of correctness.

## When to use

Core major/minor upgrades (9→10, 10→11, 11→12, 10.x→11.x minors with deprecations), custom module modernization, contrib major bumps, PHP/Symfony compatibility. For Drupal 7 sites this is a migration (`drupal-migrate-api`) plus the `drupal-legacy-archaeologist` agent: do not rewrite D7 code as modern Drupal inside this skill.

## Procedure ([references/workflow.md](references/workflow.md) has the full sequence)

1. **Inventory, printed before any edit** using table 1 of [references/report-template.md](references/report-template.md): profile (current version, PHP, Drush, patches, contrib list, custom modules), and for each custom module the APIs it uses with file:line (`grep` for hooks, services, base classes, annotations, deprecated functions from the facts registry).
2. **Target**: exact target minor and its platform ([references/version-jumps.md](references/version-jumps.md)): PHP, database, Symfony, Drush, removed core modules, `core_version_requirement`.
3. **Compatibility matrix**: contrib per module (`drupal-contrib-research`: release for the target, open compat issues, patches that no longer apply), custom modules (Upgrade Status / phpstan-drupal with deprecation rules / Rector dry run, [references/tooling.md](references/tooling.md)), PHP (`drush pm:security-php`, phpstan `phpVersion`).
4. **Composer**: constraints for the target (`composer require drupal/core-recommended:^11.4 drupal/core-composer-scaffold:^11.4 drupal/core-dev:^11.4 --update-with-all-dependencies --dry-run` first); never loosen constraints or add `composer-drupal-lenient` silently; record every constraint change.
5. **Classify every change in table 2 of the report template before implementing** — `| change | automated (Rector rule / sed-able) | manual | why |`. Automated = a Rector rule exists or the edit is mechanical (function → static method, deprecated service ID); manual = behaviour change, removed API without 1:1 replacement, annotation→attribute for contrib plugin types, hook conversions, tests. Produce the table even when Rector is not installed (state that it was not run); every automated change is still read and tested.
6. **Implement** module by module: replacement APIs verified in the *target* core (`drupal-lookup` against a target checkout or lab), `core_version_requirement` updated, tests updated only where the behaviour legitimately changed.
7. **Verify against the target**: phpcs, phpstan (deprecation rules on), PHPUnit, then install/enable on the target (disposable lab if the project has no target environment), `drush updb`, `drush cr`, the key user workflows, logs. Record `VERIFY` lines per level; unverified steps are `NOT VERIFIED`.
8. **Report** with table 3 of the template: the inventory and classification tables (as printed earlier), changed APIs (old → new → citation), constraint changes, `VERIFY` lines, remaining incompatibilities with owners (contrib issue links), deployment order.

## Decision rules

- Multi-version support (`^10.3 || ^11`) only when tests run on both; otherwise target one.
- Deprecations first, then the core bump: a module clean on deprecation rules upgrades predictably.
- Contrib without a target release blocks the upgrade or gets replaced; do not fork silently.
- Procedural hooks stay procedural in an upgrade unless the task is the OOP conversion; `#[Hook]` needs ≥ 11.1 (`drupal-facts check oop-hooks`).
- Large inventories and Rector loops go to the `drupal-upgrade-specialist` agent.

## Works with process skills

Architectural class in `drupal-workflow`; with Superpowers, brainstorming produces the target/plan, SDD executes per module with this skill named in each brief.

## Red flags

| Thought | Reality |
|---|---|
| "Run Rector, commit, done" | Rector output is a diff to review and test against the target. |
| "It boots on 11, ship it" | Deprecated calls become fatals on the next minor; run deprecation rules. |
| "Loosen the constraint so composer resolves" | You are installing an untested combination; say it and ask. |
| "I'll convert all hooks to attributes while I'm here" | Scope creep; the upgrade is the task. |
