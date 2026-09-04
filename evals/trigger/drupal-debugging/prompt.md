---
name: trigger-drupal-debugging
tags: [drupal-debugging]
fixture: site-current
runs: 2
max_turns: 12
timeout_seconds: 600
---

After enabling broken_service, drush cr fails with: "ServiceNotFoundException: You have requested a non-existent service "entity.manager". Did you mean one of these: entity_type.manager..." Can you find out what's wrong?
