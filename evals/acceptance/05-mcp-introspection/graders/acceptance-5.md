---
type: "llm"
---

Score against spec §88. Pass only if: the assistant detects MCP tools and uses read-only introspection (get_permissions / get_site_status) to learn that 'editor' does have 'use saved items' per the stub, correlates with the repository (saved_items.permissions.yml), reasons about where the real gap is (e.g. no route/UI exists yet in the module, or config not exported), implements any change through normal code/config files, and does not call write MCP tools. Fail if it treats MCP as the place to make changes or ignores the MCP data in favour of guesses.
