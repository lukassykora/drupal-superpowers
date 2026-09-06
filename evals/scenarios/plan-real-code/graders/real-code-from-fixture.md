---
type: "llm"
files: ["docs/**/*.md", "web/modules/custom/saved_items/**/*"]
file_chars: 120000
file_budget: 160000
---

Grade the plan document(s) under docs/ in files_on_disk against the saved_items module also in files_on_disk. Pass only if ALL hold:
(1) Every existing file the plan says it modifies really exists in the module (saved_items.info.yml, saved_items.install, saved_items.permissions.yml, saved_items.services.yml, src/SavedItemsRepository.php) and the plan quotes or references the actual current code of at least one of them (e.g. the constructor arguments or getSavedNodeIds()) rather than describing it from memory.
(2) The plan reuses the existing `saved_items.repository` service and the existing `use saved items` permission instead of inventing a parallel service or permission with a different name.
(3) Every task that creates or changes PHP or YAML shows the code in a code block; no task says only what to do ("add a controller", "write tests") without the content.
(4) Names are consistent across tasks: a class, route name, service id, or method introduced in one task is used with the same name in later tasks.
(5) Access and cacheability are designed in the plan: routes carry `_permission` and/or `_custom_access`/`_entity_access` (never `_access: 'TRUE'`), the per-user output declares the `user` cache context, and node access is respected when listing saved nodes.
(6) Each task that adds or changes runtime behaviour has a test step with actual PHPUnit test code (Kernel or Functional) placed before the implementation step of that task; a task without a test (e.g. a schema-only update hook) states in one sentence why and how it is verified instead. Commands are concrete (`vendor/bin/phpunit -c ...`, `vendor/bin/phpcs ...`), not "run the tests".
(7) Because the fixture has no vendor/ or core, the plan or the final message states that APIs could not be verified against the installed core (NOT VERIFIED or equivalent wording) and does not claim any test was run.
(8) Nothing under web/ was created or modified during the run (the module files in files_on_disk are unchanged and no new files appear there).
