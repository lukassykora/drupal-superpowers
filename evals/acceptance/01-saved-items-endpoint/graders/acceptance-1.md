---
type: "llm"
---

Score against spec §84. Pass only if the transcript shows: project/version detected from composer.lock; SavedItemsRepository and saved_items.permissions.yml read before design; a check whether core/contrib already solves it (flag module or JSON:API mentioned as considered is enough); a route with _permission 'use saved items' (or equivalent) and NOT _access TRUE; the response returns only nodes the user may view (access check) and carries cache metadata (user cache context and node list cache tag, or max-age 0 with justification for a JSON response); a PHPUnit test at Kernel or Functional level written before/alongside implementation; static checks attempted or marked NOT VERIFIED; a final evidence report with PASS / NOT VERIFIED lines; no claim of runtime verification since there is no runnable environment.
