---
name: trigger-drupal-testing
tags: [drupal-testing]
fixture: site-current
runs: 2
max_turns: 15
timeout_seconds: 600
---

Write a test that proves SavedItemsRepository::getSavedNodeIds() returns the user's saved nodes newest first.
