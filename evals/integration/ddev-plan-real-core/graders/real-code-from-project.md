---
type: "llm"
files: ["docs/**/*.md", "web/modules/custom/saved_items/**/*"]
file_chars: 120000
file_budget: 160000
---

Grade the plan document(s) under docs/ in files_on_disk against the saved_items module also in files_on_disk. This project is a real Drupal 11 site run with DDEV: web/core and vendor exist and the site is running. Pass only if ALL hold:
(1) Every existing file the plan says it modifies really exists in the module and the plan quotes or references its actual current code (e.g. the constructor arguments or getSavedNodeIds()) rather than describing it from memory.
(2) The plan reuses the existing `saved_items.repository` service and the existing `use saved items` permission instead of inventing a parallel service or permission.
(3) Every task that creates or changes PHP or YAML shows the code in a code block; no task only says what to do without the content.
(4) Names are consistent across tasks.
(5) Access and cacheability are designed: routes carry `_permission` and/or `_custom_access`/`_entity_access` (never `_access: 'TRUE'`), the per-user output declares the `user` cache context, node access is respected when listing saved nodes.
(6) Each task that adds or changes runtime behaviour has PHPUnit test code (Kernel or Functional) placed before the implementation step; a schema-only task may state in one sentence why there is no test. Every command in the plan goes through DDEV (`ddev exec vendor/bin/phpunit …`, `ddev drush …`, `ddev exec vendor/bin/phpcs …`), never bare host commands.
(7) At least one core API the code depends on was verified against the installed core in this project (a `web/core/...` file path or `drupal-lookup` output appears in the transcript or the plan), and APIs that were not checked are marked NOT VERIFIED rather than asserted.
(8) Nothing under web/ was created or modified during the run.
