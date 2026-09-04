---
name: trigger-drupal-config
tags: [drupal-config]
fixture: site-current
runs: 2
max_turns: 8
timeout_seconds: 300
---

Should the reporting time zone setting for our reports module live in config or in state? It's set once per environment by an admin.
