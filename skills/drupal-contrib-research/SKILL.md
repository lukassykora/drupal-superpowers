---
name: drupal-contrib-research
description: Use when considering a contrib module, theme, or Composer package for a Drupal need, before building non-trivial custom functionality, or when evaluating a module's Drupal version support, release status, maintenance, security coverage, and constraints.
---

# Drupal contrib research

**Core principle:** popularity is a signal, not evidence. A contrib module is a dependency with a maintenance contract; evaluate it like one, against this project's Drupal version and constraints, before writing custom code and before adding it.

## When to use

Before building anything non-trivial that Drupal core does not provide; when the user proposes a module; when a dependency must be added, upgraded, or replaced. Not for trivial changes or for modules already in the project (read their source instead).

## Procedure

1. **Name the need in Drupal terms** (e.g. "scheduled publishing of nodes with per-entity dates"), then check **core** first: `drupal-lookup --kind pattern`, core module list for this version. Core wins when it covers the need.
2. **Candidates**: search drupal.org (`https://www.drupal.org/project/project_module?text=<need>&drupal_core=<major>`), the project's existing contrib list (profile `modules.contrib`), and, if reachable, Drupal Code Query / drupal.org API:

   ```bash
   "${CLAUDE_SKILL_DIR}/scripts/contrib-info" <machine_name> [<drupal major>]
   ```
   which prints releases per core branch, maintenance/development status, security coverage, usage, and open critical issues from drupal.org's REST API (or `unavailable` offline).
3. **Evaluate each candidate** with [references/evaluation-criteria.md](references/evaluation-criteria.md): supported Drupal versions (`core_version_requirement` of the latest release, not the project page banner), release status (stable vs alpha/beta/dev), maintenance status, security advisory coverage, usage trend, open critical/major issues, compatibility issues for the target version, Composer constraints and dependencies, maintainer activity, superseded/obsolete notices, what it will not do.
4. **Decide**: core > maintained contrib with security coverage > small custom code > contrib that is alpha/unmaintained/uncovered. Write the decision with the deciding criterion.
5. **Adding it** (`composer require drupal/<name>:^<x>` through the adapter, on LOCAL/DISPOSABLE or with approval): keep constraints tight, check `composer why-not`, review new dependencies, enable via config export, write down the deploy steps. Patches via `cweagans/composer-patches` with the issue URL and a comment; never edit `modules/contrib` directly.
6. Report: candidates table (name, version for this core, status, security, usage, verdict) and the recommendation.

## Decision rules

- No stable release for this Drupal major → custom or wait, unless the project already accepts alphas and the user agrees.
- Not covered by the security advisory policy → acceptable only for small, reviewable modules; say so.
- Large module for a small need → prefer the small custom implementation with a test.
- Never loosen `composer.json` constraints or add `composer-drupal-lenient` to make a module install without stating the compatibility risk.
- A module's API is its source in `vendor/`/`modules/contrib`, not its project page.

## Works with process skills

In `superpowers:brainstorming` this is the "existing solutions" part of approaches; in `drupal-architecture` it is the core/contrib/custom table.

## Red flags

| Thought | Reality |
|---|---|
| "Module X has 100k installs" | For which core version? Check the release for this major and its date. |
| "Dev release is fine for now" | It ships; a dev branch has no security coverage and no upgrade path guarantee. |
| "I'll patch it quickly" | Patches need an issue, a test, and a maintainer path, or they rot. |
| "Custom is always more work" | Fifty lines with a test can beat a 10k-line dependency. |
