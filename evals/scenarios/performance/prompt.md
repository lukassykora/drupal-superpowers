---
fixture: site-current
runs: 1
tags: [performance]
max_turns: 25
timeout_seconds: 600
---

The partner listing page at /partners (partner_directory module) is slow with a few hundred partner nodes. Fix the cause without changing what the page shows.

<!-- runner notes: the prompt must not start with a slash; Claude Code treats a leading /word as a slash command (observed: "Unknown command: /partners"). -->
