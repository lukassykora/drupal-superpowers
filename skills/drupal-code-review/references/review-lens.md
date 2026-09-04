# Drupal review lens

Questions per area, with what to open. Severity guidance in brackets.

## Correctness
- Does the code do what the task says, on the path a user actually takes? Trace route → controller/form → service → storage → output. [CRITICAL/HIGH]
- Error paths: missing entity (404 vs exception), empty results, invalid input, exceptions from services. [HIGH/MEDIUM]
- Concurrency/idempotence for update hooks, queue workers, cron. [MEDIUM]

## Drupal API correctness for this version
- Every API used exists in the project's core (`drupal-lookup`); deprecated APIs flagged (`@deprecated`, facts registry). [HIGH]
- Attributes vs annotations, `#[Hook]` availability, `accessCheck()` on entity queries, PHPUnit attribute style, Symfony signatures. [HIGH]
- DI used in classes; `\Drupal::` only where acceptable. [MEDIUM]
- Right mechanism: service/plugin/event/hook; config vs state vs tempstore (`drupal-architecture` tables). [MEDIUM]

## Security (`drupal-security` checklist)
- Untrusted input → output path escaped; no `Markup::create`/`|raw`/`#markup` interpolation of request or field data. [CRITICAL]
- Routes with requirements; CSRF on state changes; SQL placeholders; file validators; redirect targets trusted; no secrets in code/config/logs. [CRITICAL/HIGH]

## Access (`access-patterns`)
- Route requirement matches the capability; entity access checked after load/query; field access where raw values are read; owner checks for personal data. [CRITICAL/HIGH]
- Access results carry cacheability. [HIGH]

## Cacheability (`drupal-cacheability`)
- Every render array/response/block declares contexts for what it varies by (user, permissions, language, query args) and tags for what it shows. [HIGH]
- `max-age: 0` justified; personalized fragments behind lazy builders where the page should stay cacheable. [MEDIUM]
- Custom tags invalidated where data changes. [MEDIUM]

## Configuration
- `config/install` has schema; dependencies declared; existing sites get post-update/update hooks; no environment-specific values in config. [MEDIUM]
- Exported config in the sync dir consistent with the change (roles/permissions, views). [MEDIUM]

## Tests
- Tests exist at the level that proves the behaviour (Kernel for services/entities/access, Functional for routes/permissions). [MEDIUM]
- Tests were executed (evidence in the PR or your run); assertions meaningful; no skipped/`markTestIncomplete` without reason; no mocks of the subject. [HIGH if fake]
- Bugfix includes a regression test that failed before the fix. [MEDIUM]

## Coding standards and maintainability
- phpcs Drupal/DrupalPractice clean; docblocks (`Implements hook_X().`, `{@inheritdoc}`); `t()` for UI strings; naming per project. [LOW]
- No unrelated refactoring, no dead code, no boilerplate files. [LOW]
- Identifiers, machine names, comments, docblocks, and YAML labels in English; user-facing text through `t()` (non-English hard-coded strings or identifiers are a finding). [LOW; MEDIUM for machine names, which cannot be renamed later without an update path]
- Readable: one responsibility per class/method; no hidden side effects. [LOW]

## Deployment impact
- What must run on deploy: updb, post-update, cim, cr, reindex, node access rebuild; ordering matches the project's process. [HIGH if missing]
- Data migrations reversible or documented. [MEDIUM]

## Backward compatibility
- Public APIs (services, hooks, plugins used by other modules) unchanged or deprecated properly; `core_version_requirement` accurate; contrib-shaped modules keep BC across the supported range. [MEDIUM]

## Cannot verify from diff
List explicitly: runtime behaviour, test execution, config on real sites, performance under data volume. Mark ⚠️ and suggest the command that would verify.
