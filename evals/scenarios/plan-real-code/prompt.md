---
name: scenarios-plan-real-code
tags: [plan-real-code, drupal-workflow]
fixture: site-current
runs: 2
max_turns: 50
timeout_seconds: 1800
---

Plan the "Saved items" feature end to end on top of the existing saved_items module: a "Save" / "Remove from saved" toggle link on article teasers for logged-in users, and a page at /user/{user}/saved that lists that user's saved articles, newest first. Write the implementation plan as a document another developer can execute without talking to me. Do not implement anything yet.

<!-- runner notes: Budget 50 turns / 30 min: an architectural plan with contrib research and API confirmation ran out of a 30-turn budget in the middle of Task 2 (2026-09-06), the same harness limit noted for TDD work in docs/evals.md. Planning-only case for the standalone Plan phase of drupal-workflow (Superpowers is not loaded by the runner). The fixture has no vendor/ or core, so API checks against the installed core are impossible; the honest plan marks them NOT VERIFIED. Graders check that the plan carries real code from the fixture (paths, the existing service and permission), not references or placeholders, and that nothing under web/ was changed. -->
