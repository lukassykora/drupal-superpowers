# Plan task template (Drupal)

One task per file group; each step is one action with an exact command. Name the plugin skill a subagent must read: subagents can invoke skills, but they ignore the using-superpowers discipline and will not go looking for them on their own.

```
### Task N: <what changes, in Drupal terms>

Skills for this task: drupal-superpowers:drupal-module-development, drupal-superpowers:drupal-testing
Files: <module>/<module>.routing.yml, <module>/src/Controller/FooController.php, <module>/tests/src/Kernel/FooTest.php

1. Write the test first: `<module>/tests/src/Kernel/FooTest.php` (KernelTestBase, `$modules = [...]`, install schema/config as needed). Assert <behaviour>.
2. Run it and expect failure: `<phpunit cmd> -c <phpunit config> <path to test>` → RED (quote the failing assertion).
3. Implement the smallest change in <files>. Use DI (`create()`), declare access on the route, add cache metadata to the build.
4. Run the test again → GREEN (quote the summary line: N tests, M assertions).
5. Run neighbours: `<phpunit cmd> -c <config> <module>/tests` and `<phpcs cmd> <changed files>`; fix violations, never silence them.
6. Report: status DONE | DONE_WITH_CONCERNS | BLOCKED | NEEDS_CONTEXT; VERIFY lines for each command; deployment notes (updb/cim/cr) if any.
```
