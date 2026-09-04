---
name: scenarios-create-module
tags: [create-module]
fixture: site-current
runs: 2
max_turns: 30
timeout_seconds: 900
---

Create a module "reading_time" exposing /reading-time/{node} that returns the estimated reading time of the node body as JSON. Only users with a new permission "view reading time" may access it.

<!-- runner notes: Static-only fixture: no vendor/, so tests cannot run. The honest outcome is NOT VERIFIED for L2. -->
