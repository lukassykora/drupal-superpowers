---
name: drupal-debugging
description: Use when a Drupal site or test shows an error or wrong behaviour: WSOD, "The website encountered an unexpected error", ServiceNotFoundException, plugin not found, route not found, stale cache, config import or schema errors, access denied, failing drush cr; after reproducing, before changing code.
user-invocable: true
argument-hint: "[symptom or error text]"
model: opus
effort: high
---

# Drupal debugging

**Core principle:** reproduce, then read the evidence Drupal already produced, then trace the execution path, then form and falsify hypotheses. Code changes come after the root cause, and `drush cr` is a diagnostic step, not a fix.

## When to use

Any error, failing test, or "it does the wrong thing" in a Drupal project. Not for feature work (`drupal-workflow`) or for review (`drupal-code-review`).

## Procedure

1. **Symptom, verbatim.** Copy the exact message, HTTP status, URL, user/role, and when it started (deploy, module enable, config import, update).
2. **Reproduce** through the resolved runtime (`drupal-runtime-verification`): the URL as the affected role, the failing test file, `drush cr`, `drush status`. If it cannot be reproduced here, say so and work from logs.
3. **Read the evidence** ([references/log-sources.md](references/log-sources.md)): `drush watchdog:show --severity=Error --count=20`, PHP/web server/container logs, the full exception with its trace (`$config['system.logging']['error_level'] = 'verbose'` on LOCAL only), browser console/network for JS/AJAX, test output including the first failure not the last.
4. **Match the failure family** in [references/drupal-failure-modes.md](references/drupal-failure-modes.md) (container/service, plugin discovery, routing, config schema/drift, permissions/access, render/Twig cache, libraries/behaviors, AJAX, queue/cron, update hooks, DB schema, migrations, multilingual/revisions, Composer/patch conflicts, deprecations/Symfony). Each family lists where the real cause usually lives and the diagnostic command.
5. **Trace the execution path** from the entry point to the failure: route → controller/form → service → entity/storage → template. Read those files; name them.
6. **Hypotheses, then falsification.** Write 1–3 hypotheses, each with the observation that would disprove it; run that observation first. Stop at the one that survives.
7. **Regression test** (`drupal-testing`) that fails for this cause, then the **minimal fix**, then green, then neighbours, then runtime check.
8. Report: symptom → cause (file:line) → fix → evidence lines; side findings separately.

## Decision rules

- After `drush cr` the symptom persists → the cause is in code/config, not cache; do not clear again.
- `ServiceNotFoundException` / "class not found" → read `*.services.yml` and the class file path/namespace before anything else.
- "Plugin not found" → discovery (attribute/annotation form, namespace `Plugin/<Type>`, cache) before logic.
- Access denied → route requirement vs entity access vs field access; check each layer with the actual account.
- Works logged in, wrong for anonymous / other user → cacheability (`drupal-cacheability`), not logic.
- Config import fails → schema, dependencies, missing module, UUID mismatch; never `cim -y` to "push through".
- Noisy investigations (large logs, many files) go to `drupal-researcher` or `drupal-test-engineer` agents; they return causes, not logs.

## Works with process skills

`superpowers:systematic-debugging` owns the four-phase discipline; this skill is its Drupal evidence, failure families, and commands (its Phase 1–2). When it is active, do not restate its rules; run steps 2–5 here inside its phases.

## Red flags

| Thought | Reality |
|---|---|
| "Let me clear the cache and see" | Fine once, as a diagnostic; twice is guessing. |
| "Probably a permissions issue, I'll add the permission" | Which layer denied? Prove it with the account and the access check. |
| "I'll change the service argument and try" | Read the container error; it names the missing ID. |
| "The test is flaky" | Flaky means a race or shared state; find it. |
| "Disable the module to make the error go away" | That removes the symptom and the feature. |
