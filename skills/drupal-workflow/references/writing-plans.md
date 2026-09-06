# Writing a Drupal implementation plan

**Core principle:** a plan is the code the implementer will type, taken from files read in this session and from APIs located in the installed core. Prose that describes code ("add a controller", "write a Kernel test for the repository") is a placeholder, and a placeholder is a plan defect.

When `superpowers:writing-plans` is active, it owns the document shape (header, bite-sized steps, self-review, execution handoff); take from here only §1 (read first), §3 (Drupal task rules), and the two templates. Standalone, this reference is the Plan phase of `drupal-workflow`.

## 1. Read before you write, write before you verify

The order is fixed, because a plan that exists with `NOT VERIFIED` marks is worth more than a perfect plan that was never written:

1. **Profile and runtime** from the Orient phase: version + class, docroot, custom module path, the resolved `phpunit`, `phpcs`, `phpstan`, `drush` commands. A plan whose `Run:` lines say bare `phpunit` or `drush cr` is wrong for this project.
2. **Every file the plan modifies**, in full or the affected range, and write down `path:from-to`. For every file the plan creates, read the nearest sibling in the same module (the neighbouring controller, the existing test, the existing `*.services.yml`) so the new code copies its conventions: namespace, DI style, PHPUnit style, docblocks.
3. **Existing mechanisms to reuse**: service ids in `*.services.yml`, permissions in `*.permissions.yml`, routes, config schema, the schema in `hook_schema()`. The plan extends them; it never adds a parallel service or permission with a new name for the same thing.
4. **Installed core, when present**: for every core or contrib API the code blocks call (base class, interface, service id, hook, plugin attribute, Twig function) open it under `<docroot>/core` or `<docroot>/modules/contrib/<module>` (or `drupal-lookup`), quote the signature you rely on, and record the file path with the line (`web/core/lib/Drupal/Core/Controller/ControllerBase.php:41`) in the header's API table; a plan that says "verified" without a path was not verified. Without installed core, skip this step: write the document now (§2) with every such API marked `API NOT VERIFIED — no installed core; confirm <Class::method> before Task N`, in the header table and in the task.
5. **Version gate** from the router line: hook style (`#[Hook]` only from 11.1, otherwise procedural), plugin attributes vs annotations, PHPUnit attributes vs annotations, deprecated APIs to avoid. The decision is written into Global Constraints, not left to the implementer.
6. **Verification pass, after the file exists**: walk the header's API table once. One look-up per row that a code block's signature or namespace depends on (api.drupal.org or the change record for the project's branch, or one `drupal-researcher` dispatch for the whole table); update the row to `confirmed on the <branch> branch, NOT VERIFIED against this checkout`; leave the other rows for the implementer to confirm after `composer install`. Do not download core files one by one, and do not extend the pass to usage statistics, release histories, or design questions that the Design phase already settled.

## 2. The document

Save to `docs/plans/YYYY-MM-DD-<feature>.md` in the project root (the user's or project's convention wins). Never inside `web/`, a module, or a theme: a plan there ships with the code. English, whatever the conversation language.

```markdown
# <Feature> implementation plan (Drupal)

**Goal:** one sentence.
**Design:** the mechanism chosen in the Design phase (service / plugin / hook / entity …), one paragraph, with the decision-table row that applies.
**Profile:** Drupal <version> (<class>), PHP <constraint>, runtime <adapter>/<environment class>, docroot <path>.
**Files read for this plan:** `<path>:<lines>` per file, plus the core files opened for API checks.
**API verification:** one row per API: `<Class::method or hook>` → `<docroot>/core/<path>.php:<line>` (verified) | `NOT VERIFIED — <reason>` (confirm before Task N).

## Global Constraints (Drupal)
<paste references/global-constraints-template.md, filled>

## Deployment
updb / cim / cr / reindex / permission grants required after merge, or "none".

## Out of scope
What was deliberately left out and where it was reported.

## Tasks
<one block per task, see §3>
```

Task order for Drupal work: module skeleton (`.info.yml`, permissions, services, `hook_schema`) → storage or service → access (route requirements, access handlers) → route + controller or form → render and cache metadata → theme or front-end → config and schema. Tests are inside every task, never a final "Tests" section.

## 3. Task rules (Drupal)

Use [plan-task-template.md](plan-task-template.md) for every task. On top of the generic step order (test first, RED, implement, GREEN, standards, report):

- **Files by file class.** Every YAML touched is its own line: `*.routing.yml`, `*.permissions.yml`, `*.services.yml`, `*.links.*.yml`, `config/schema/*.schema.yml`, `config/install/*.yml`. `Modify:` lines carry the line range read in §1.
- **Interfaces block** with exact service ids, FQCNs, method signatures and route names later tasks use. The implementer of Task 4 sees only Task 4.
- **Test code is code.** The test step contains the PHPUnit class (Kernel by default, per `drupal-testing`), with `$modules`, the schema/config installs, and the assertions. The RED step quotes the expected failing assertion. A bullet list of "cover: …" or "write `FooTest` (`KernelTestBase`, `$modules = […]`)" is a placeholder.
- **Schema-only or config-only tasks** (an update hook adding an index, a `config/install` file) may replace the test step with one sentence saying why there is no test and how the task is verified instead (`drush updb` + schema inspection, `drush config:status`).
- **Each task is self-contained.** "Implement §5.2" or "as in Task 1" is a placeholder: the implementer sees one task, so the class, the YAML, and the test are pasted into the task even when a design section above already showed the signatures.
- **Routes show their `requirements:`** (`_permission`, `_custom_access`, `_entity_access`, `_csrf_token` where a GET link changes state) and `options: parameters` for entity upcasting, in the YAML block. `_access: 'TRUE'` needs a written reason.
- **Cache metadata lives in the code block**: contexts (`user`, `user.permissions`, `url.query_args`, …), tags, max-age, `#lazy_builder` where personalised output would fragment the render cache. Not as a note under the block.
- **Config comes with schema** in the same task; environment-specific values go to settings, never to `config/install`.
- **Deployment lines per task**: `hook_update_N()` when the schema changed on an installed module, `drush cr` for new routes/services/hooks, `drush cex` after permission or config changes.
- **No commit step.** A task ends with the report line and the `git add` / `git commit` lines the user can paste ([../../drupal-verification/references/git-handoff.md](../../drupal-verification/references/git-handoff.md)).
- **Skills line**: name `drupal-superpowers:<skill>` for a subagent executing the task; subagents do not go looking for skills.

## 4. Self-review before handing over

Run the checklist yourself, fix inline, no re-review:

1. **Coverage:** every clause of the request maps to a task; every design decision from the Design phase appears in a task.
2. **Placeholders:** search for `TBD`, `TODO`, "similar to Task", "add validation", "handle edge cases", and any step that describes code without showing it.
3. **Names:** service ids, route names, class names, permissions, and method signatures match across tasks.
4. **Read-first:** every `Modify:` path was read in this session; every quoted range exists.
5. **Version gate:** every API in the code blocks exists in the profile's version, or carries the `NOT VERIFIED` mark.
6. **Commands:** every `Run:` line uses the resolved command and config file (`<phpunit cmd> -c <config> <path>`).
7. **Drupal review rows** ([../../drupal-architecture/references/design-review-checklist.md](../../drupal-architecture/references/design-review-checklist.md)): access, cacheability, config schema, translations (`t()` / `TranslatableMarkup`), deployment, backward compatibility.

## 5. Handoff

Report the path, the API verification status, and the execution choice:

- With Superpowers: `superpowers:subagent-driven-development` (fresh subagent per task) or `superpowers:executing-plans` (another session).
- Standalone: execute in this session task by task (Implement → Verify per task, one `drupal-verification` gate at the end), or a fresh session starts by reading the plan and its Global Constraints.

When the user asked for a plan only, the Plan phase is the last one: do not start Task 1.

## Red flags

| Thought | Reality |
|---|---|
| "I know `ControllerBase`, no need to open it" | The version decides what exists; quote the installed signature or mark `NOT VERIFIED`. |
| "The implementer will write the tests" | Test code is part of the task or the task is a placeholder. |
| "I'll reference the existing controller rather than paste it" | The implementer sees one task; paste the lines that matter. |
| "A commit step keeps tasks atomic" | Git is the user's; end with the paste-ready commands. |
| "Cache metadata is an implementation detail" | It decides whether personalised output leaks; it belongs in the code block. |
| "The plan is documentation, it can live next to the module" | Anything under `web/` ships; plans go to `docs/plans/`. |
| "The design section already has the code, the task can point at it" | Tasks are executed one at a time by someone who reads only that task; repeat the code. |
| "api.drupal.org confirmed it, so it is verified" | Verified means the installed core; the web confirms the branch, not this checkout. Say which. |
| "I'll confirm every API before I write a line" | The file first, with `NOT VERIFIED` marks; the verification pass runs over the finished table, once. |
| "One more core file from git.drupalcode.org and I'm sure" | Without installed core the mark stays whatever the web says; the implementer confirms after `composer install`. |
