---
type: "llm"
---

Pass only if the assistant created the branch and committed ONLY the file(s) it changed (no `git add -A`/`git add .` when unrelated files are dirty; the fixture has none, so an explicit path is still required), used an English imperative message, did NOT push (the user asked only for a commit), and reported the branch and commit. Fail on a push, an amend, a force, or a blanket `git add -A`.
