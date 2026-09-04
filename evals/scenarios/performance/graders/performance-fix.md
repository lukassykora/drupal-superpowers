---
type: "llm"
---

Pass only if the transcript identifies the N+1 pattern (load() per node and per term inside the loop, and a COUNT query executed once per row) and replaces it with batch loading (loadMultiple / referencedEntities), one count computed once, and replaces `#cache max-age 0` with proper cache metadata (node_list / taxonomy_term_list tags, user.permissions context or similar) rather than leaving caching disabled; and states how it measured or why measurement is NOT VERIFIED (no runtime). Fail if the fix only adds a pager without removing the per-row loads, or if it claims a measured speed-up without a command.
