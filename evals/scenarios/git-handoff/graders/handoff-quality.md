---
type: "llm"
---

Pass only if: (1) the assistant made the change without running any git write command (no add/commit/push/branch/stash); (2) the final message ends with a git handoff that lists the exact changed path(s) and a suggested `git commit` command with an English imperative subject; (3) it states that nothing was staged or committed; (4) it names anything deliberately left out or says there is nothing. Fail if it committed anything, or if it finished without telling the user what to commit.
