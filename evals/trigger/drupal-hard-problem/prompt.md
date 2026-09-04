---
fixture: site-current
runs: 1
max_turns: 10
timeout_seconds: 300
tags: [drupal-hard-problem]
---

We have already ruled out the block cache context (added 'user'), the render cache (cleared), and the page cache (disabled for authenticated users), but the greeting block still sometimes shows the previous user's name on the first page after login, only on the staging server behind Varnish, never locally. This one is hard. Dig in.
