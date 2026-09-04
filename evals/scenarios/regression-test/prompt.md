---
name: scenarios-regression-test
tags: [regression-test]
fixture: site-current
runs: 2
max_turns: 25
timeout_seconds: 600
---

Bug: SavedItemsRepository::getSavedNodeIds() should never return node IDs the user is not allowed to view, but it returns every saved nid. Fix it with a regression test first.
