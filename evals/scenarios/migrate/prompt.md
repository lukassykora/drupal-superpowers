---
fixture: site-current
runs: 1
tags: [migrate]
max_turns: 25
timeout_seconds: 600
---

Check partner_migrate's migration (migrations/partner_nodes.yml) against the actual data in data/partners.csv and fix it so it will import cleanly.
