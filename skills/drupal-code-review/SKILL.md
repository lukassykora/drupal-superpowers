---
name: drupal-code-review
description: Use when reviewing a Drupal diff, module, or pull request for correctness, Drupal API use, version compatibility, security, access, cacheability, configuration, tests, coding standards, and deployment impact, or when asked to review Drupal code.
user-invocable: true
argument-hint: "[path, module, or PR]"
model: opus
effort: high
---

# Drupal code review

**Core principle:** the reviewer's job is to find reasons the change could fail on this Drupal version, for this project's users, on deploy. Findings are ranked by consequence, not by style; the implementer's description of the change is not evidence.

## When to use

Reviewing any Drupal change: your own before claiming done (light pass), someone else's (full pass), or as the lens given to the `drupal-code-reviewer` / `drupal-security-reviewer` agents. For non-trivial changes the review is done by a different agent than the implementer.

## Procedure

1. **Scope the diff**: `git diff <base>...` or the module directory; list files by class (routing/services YAML, PHP classes, config, tests, Twig). Note the Drupal version class from the profile.
2. **Read the code paths, not just the diff hunks**: for each changed route/controller/service, follow the execution to entities and output. Open the tests and check what they assert.
3. **Apply the lens** in [references/review-lens.md](references/review-lens.md), in this order: correctness → Drupal API correctness for this version → security → access → cacheability → configuration/schema → tests (present, executed, meaningful) → coding standards → maintainability → deployment impact → backward compatibility.
4. **Verify claims**: run L1 on the changed files and the module's tests if a runtime exists; otherwise mark `NOT VERIFIED` and say what you could not check from the diff alone (⚠️ cannot verify from diff).
5. **Write findings** with file:line, severity, the failure scenario, and the Drupal-native fix:

   ```
   CRITICAL  src/Controller/NotesController.php:24  Reflected XSS: query parameter `highlight` wrapped in Markup::create(). Fix: '#plain_text' or t() placeholder.
   HIGH      xss_notes.routing.yml:7  `_access: 'TRUE'` on a route that renders node fields; unpublished nodes readable. Fix: `_entity_access: 'node.view'`.
   HIGH      src/Plugin/Block/GreetingBlock.php:41  Output varies per user, no `user` cache context → cross-user leak via render cache.
   LOW       …
   INFO      …
   ```
   Severity: CRITICAL (data loss, security, wrong data to users), HIGH (broken feature, cache leak, missing access), MEDIUM (bug under some conditions, missing test, missing schema), LOW (standards, naming), INFORMATIONAL (suggestions).
6. **Verdict first line**: `APPROVE`, `APPROVE WITH CHANGES (n)`, or `REQUEST CHANGES (n critical/high)`; then the findings; then what was verified and how. Out-of-scope observations go in a separate short list.

## Decision rules

- A functional bug outranks any number of style findings; do not bury it.
- "Works on my machine" claims in the description count for nothing; the test evidence or your own run counts.
- Missing test for a behaviour change is at least MEDIUM; a test that cannot fail (no assertions, mocked subject) is HIGH.
- A version-incompatible API (`drupal-facts`, `drupal-lookup`) is HIGH even if it works locally on a newer core.
- Keep the review Drupal-specific: generic PHP nits only when they change behaviour.

## Works with process skills

Superpowers' SDD task reviewer has a `[GLOBAL_CONSTRAINTS]` slot; its `requesting-code-review` template has `[PLAN_OR_REQUIREMENTS]` instead. Paste the review lens summary into whichever slot the template offers so the reviewer subagent applies it. `receiving-code-review` governs how findings are acted on (verify before accepting).

## Red flags

| Thought | Reality |
|---|---|
| "The PR description says tests pass" | Run them or mark NOT VERIFIED. |
| "Looks like idiomatic Drupal" | Follow the route to the entity to the output; idioms hide missing access. |
| "Nitpicks first, they're quick" | Lead with what breaks; standards go last. |
| "I implemented it, I know it's right" | Then a different agent reviews it. |
