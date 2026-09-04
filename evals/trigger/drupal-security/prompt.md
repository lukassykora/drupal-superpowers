---
name: trigger-drupal-security
tags: [drupal-security]
fixture: site-current
runs: 2
max_turns: 12
timeout_seconds: 300
---

In xss_notes, add support for a `q` query parameter on /notes/{node} that highlights the matching words inside the note body when rendering it.
