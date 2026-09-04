---
name: trigger-drupal-security
tags: [drupal-security]
fixture: site-current
runs: 2
max_turns: 16
timeout_seconds: 600
---

In xss_notes, add support for a `q` query parameter on /notes/{node} that highlights the matching words inside the note body when rendering it.
