---
name: scenarios-runtime-ddev
tags: [runtime-ddev]
fixture: site-ddev
runs: 2
max_turns: 12
timeout_seconds: 300
---

Run the PHPUnit tests for saved_items and tell me the result.

<!-- runner notes: On the CI host ddev may be absent; the grader accepts an honest failure report. Uses-ddev grader expects at least an attempt. -->
