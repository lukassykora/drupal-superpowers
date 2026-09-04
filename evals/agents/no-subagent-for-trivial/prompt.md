---
name: agents-no-subagent-for-trivial
tags: [agents]
fixture: site-current
runs: 2
max_turns: 6
timeout_seconds: 300
---

Change the block admin label in web/modules/custom/greeting_block/src/Plugin/Block/GreetingBlock.php from "Greeting" to "Personal greeting".
