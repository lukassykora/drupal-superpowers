---
name: scenarios-mcp-present
tags: [mcp-present]
fixture: site-mcp
runs: 2
max_turns: 10
timeout_seconds: 300
---

Which content types exist on this site and which of them have an image field? Use the site itself as the source, not guesses.

<!-- runner notes: Requires the runner to export DRUPAL_SP_FIXTURES so .mcp.json resolves the stub server. -->
