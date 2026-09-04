---
name: drupal-verification
description: Use when about to say a Drupal task is done, fixed, working, or passing; produces the PASS / FAIL / NOT VERIFIED / NOT APPLICABLE completion report from coding standards, static analysis, tests, and runtime evidence.
user-invocable: true
---

# Drupal verification (completion gate)

**Core principle:** "done" is a report, not a feeling. Every gate that applies to the changed files has a status backed by a command that ran in this session; everything else says `NOT VERIFIED` with the reason.

## Procedure

1. List what changed (files, config, schema, dependencies).
2. Pick the applicable gates from [references/gate-matrix.md](references/gate-matrix.md) by file class. A README change has no cacheability gate; a controller has security, access, and cacheability gates.
3. For each applicable gate, find the `VERIFY` ledger line from `drupal-runtime-verification`. No line → run the check now if possible, else `NOT VERIFIED — <reason>`.
4. Emit the report (short form for trivial changes, full form otherwise):

   ```
   Changed:
   - web/modules/custom/x/src/Controller/FooController.php, x.routing.yml, tests/src/Kernel/FooTest.php

   Verified:
   - Drupal version detected: PASS (11.4.6, composer.lock)
   - Relevant code inspected: PASS (NodesController, x.routing.yml, core NodeAccessCheck)
   - API verified: PASS (CacheableJsonResponse in core/lib/Drupal/Core/Cache)
   - Coding standards: PASS (phpcs 0 errors)
   - Static analysis: PASS (phpstan level 6, 0 errors)
   - Automated tests: PASS (FooTest 3 tests, 7 assertions; module suite 12 tests)
   - Access review: PASS (_permission on route; node->access('view') in controller)
   - Cacheability review: PASS (user context, node_list tag)

   Not verified:
   - Runtime bootstrap: NOT VERIFIED — no runnable environment (adapter=none)
   - Live behaviour: NOT VERIFIED — same
   - Browser: NOT APPLICABLE — JSON endpoint

   Deployment: none (no schema/config change)

   To commit (your call, nothing was staged):
     git add web/modules/custom/x/x.routing.yml web/modules/custom/x/src/Controller/FooController.php web/modules/custom/x/tests/src/Kernel/FooTest.php
     git commit -m "Add …"
   Not included on purpose: config/sync/*.yml (run `drush cex` first if the permission change should deploy).
   ```
5. If any applicable gate is `FAIL`, the task is not done; say what fails and stop or fix.
6. **Hand git back to the user** ([references/git-handoff.md](references/git-handoff.md)): nothing is staged, committed, pushed, merged, rebased, or branched unless the user asked for that exact operation. End the report with the `git add`/`git commit` lines the user can paste, a message in the project's style, and what you deliberately left out.
7. Report side findings (unrelated problems noticed) in a separate short list; do not fix them unrequested.

## Decision rules

- Tests that were not executed are `NOT VERIFIED`, even if they were written.
- A test with zero assertions or an assertion weakened to pass does not count as evidence.
- Runtime claims require L2/L3 lines; static claims require L1 lines.
- When a review agent ran, cite its verdict line; when it did not and the change touches access, output, or cache, say `Independent review: NOT DONE`.

## Works with process skills

`superpowers:verification-before-completion` provides the discipline (identify → run → read → verify → claim); this skill provides the Drupal gate matrix and report shape. Use both; do not restate their rule.

## Red flags

| Thought | Reality |
|---|---|
| "Tests should pass" | Then run them, or write NOT VERIFIED. |
| "The user can run it on their side" | Fine, and the report must say it was not run here. |
| "phpstan is noisy on this project, skip it" | Report FAIL or NOT VERIFIED with the reason; never silently skip. |
| "It's a one-liner, no report" | A two-line report still names what ran. |
| "I'll just commit it, the change is obviously right" | Git is the user's. Print the commit command; let them run it. |
| "They said commit, so I'll push too" | Commit means commit. Pushing, tagging, and PRs are separate requests. |
