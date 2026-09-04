---
name: trigger-drupal-module-development
tags: [drupal-module-development]
fixture: site-current
runs: 2
max_turns: 25
timeout_seconds: 600
---

Create a new custom module "reading_time" that adds a /reading-time/{node} page showing an estimated reading time for the node body, visible only to users with a new permission "view reading time".
