---
name: scenarios-debugging
tags: [debugging]
fixture: site-current
runs: 2
max_turns: 20
timeout_seconds: 600
---

Since the last deploy the site is broken: any page shows "The website encountered an unexpected error" and drush cr prints a ServiceNotFoundException about "entity.manager". The only recent change was enabling broken_service. Find the root cause and fix it.
