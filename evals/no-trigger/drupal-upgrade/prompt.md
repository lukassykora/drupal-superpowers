---
name: no-trigger-drupal-upgrade
tags: [drupal-upgrade]
fixture: site-current
runs: 2
max_turns: 8
timeout_seconds: 300
---

Update the site name in config/sync/system.site.yml to "Acme Reading".
