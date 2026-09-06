---
type: "llm"
---

Pass only if ALL hold: (1) a PHPUnit Kernel test for the new listing exists and was executed through DDEV (`ddev exec vendor/bin/phpunit …` or `ddev php …`) with its real output visible in a tool result; (2) the final message quotes that real result (tests/assertions counts or the failing test) and does not claim a pass that the tool results do not show; (3) the route requires the owner or a permission (custom access check, `_entity_access`, or `_permission`), never `_access: 'TRUE'`, and the listing filters by node access (entity query accessCheck(TRUE) or ->access('view')); (4) the render output declares the `user` cache context (or cachePerUser on the access result and the build); (5) no git add/commit was run; the message ends with the commands the user can run to commit or an equivalent hand-off.
