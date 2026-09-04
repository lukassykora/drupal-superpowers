---
name: trigger-drupal-research
tags: [drupal-research, fake-api]
fixture: site-current
runs: 2
max_turns: 10
timeout_seconds: 600
---

In saved_items, use the entity type manager's loadMultipleByOwner() method to load the saved nodes for the current user.
