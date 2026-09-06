# Evals

Stage 4 deliverable. The eval suite exists **before** any SKILL.md so that every skill's first version is measured against a baseline run without the plugin (spec §66–68, D9). Inventory as of 2026-09-06 (measured): **80 cases** (trigger 21, no-trigger 21, scenarios 22, agents 2, acceptance 5, integration 9), 186 graders, 9 fixtures + the compose lab recipe and the MCP stub.

## 1. Why evals come first

Skill descriptions are trigger surfaces; skill bodies shape behaviour. Neither can be judged by reading. The suite gives three signals per change: did the right skill fire (trigger), did the wrong one stay silent (no-trigger), and did the outcome meet the brief (scenarios, acceptance). A description or body may only change together with its before/after results.

## 2. Layout

```
evals/
├── trigger/<skill>/              20 cases: the prompt MUST activate <skill>
├── no-trigger/<skill>/           20 cases: the prompt MUST NOT activate <skill>
├── scenarios/<name>/             22 cases: 14 from spec §66 + frontend, performance, migrate, english-code, git-handoff, git-on-request, tailwind-scan-surface, plan-real-code
├── agents/<name>/                 2 cases from spec §68
├── acceptance/<nn>-<name>/        5 cases from spec §84–88 (03 and 05 tagged p2)
├── integration/                   in-place cases against real projects named by env DSP_LAB_D11 / DSP_LAB_D10 / DSP_LAB_DDEV (Stage 8)
└── results/                       runner output, git-ignored
fixtures/                          synthetic projects, see fixtures/README.md
```

## 3. Case format

Native Claude Code format plus one extension.

`prompt.md`:

```markdown
---
name: scenarios-cache
tags: [cache]
fixture: site-current          # extension: fixtures/<name> is copied to a temp cwd before the run
runs: 2
max_turns: 25
timeout_seconds: 600
---

Users report that the "Greeting" block sometimes greets them with someone else's name …
```

`graders/<name>.md`: frontmatter with `type` and its parameters; for `llm` graders the criteria are the body.

| type | parameters | passes when |
|---|---|---|
| `tool_used` | `tool` (regex on tool name), `input_match` (regex on the tool input JSON), `min`, `max` | count of matching tool calls is within `[min, max]` |
| `regex` | `pattern`, `match: contains \| not_contains`, `flags`, `scope: final (default) \| all \| results` | pattern (not) found in the final message, all assistant text, or assistant text plus tool results |
| `file_exists` | `path` (glob, relative to the temp cwd), `min_files` (default 1) | at least `min_files` files match after the run |
| `file_contains` | `path` (glob), `pattern` (regex), `match: contains \| not_contains`, `min_files` | the on-disk file content (not) matching after the run; preferred over tool-name graders because agents may edit via Bash. Set `min_files: 1` on every `not_contains` grader: an empty glob otherwise passes vacuously and proves nothing |
| `llm` | body = criteria; optional `files` (globs handed to the judge as `files_on_disk`), `file_chars` (per-file cap, default 8000), `file_budget` (all files, default 120000) | a judge model answers PASS given transcript + criteria |
| `tool_order` | `before`, `after` | reserved for Stage 5 cases (test written before implementation edit) |

Skill activation is observed as a `Skill` tool call whose input matches `(drupal-superpowers:)?<skill>\b`. Trigger cases require `min: 1`; no-trigger cases require `max: 0`.

The `fixture:` key is ours; `setup_script:` (single line or a `|` block) runs in the copied fixture before the agent starts, e.g. `git init` for the git scenario. Integration cases use `project_env: DSP_LAB_D11` (or `DSP_LAB_D10`, `DSP_LAB_DDEV`; run in place in that directory, no copy) and `reset_script` (run before and after each run; re-seeds the fixture modules with `scripts/lab-seed` and uninstalls them). The `fixture:` key is ignored for those. When the native `claude plugin eval` becomes available on this account, a `scaffold_script` shim will copy the same fixture; nothing else in the format needs to change.

## 4. Runner (`scripts/run-evals`, Stage 5)

```
scripts/run-evals [--group trigger|no-trigger|scenarios|agents|acceptance|integration]... [--case NAME]... [--tag T]...
                  [--runs N] [--no-llm] [--baseline] [--plugin-dir DIR] [--with-user-settings] [--model M]
                  [--json OUT] [--dry-run] [--keep-temp]
scripts/compare-arms [--model M] [--runs N] [--arms plugin,superpowers,both] <the same selectors>
```

`compare-arms` runs the selected cases three times, sequentially (integration cases share one project directory): `plugin` (this plugin, no user settings), `superpowers` (`--baseline --with-user-settings`: the user's own plugins, i.e. Superpowers, without this one), `both`. It prints one table with PASS/FAIL, seconds and tool calls per arm and saves it next to the three JSON files. Any statement of the form "better than Superpowers" in the docs must point at such a table.

Per case and run:
1. Copy `fixtures/<fixture>` to a fresh temp directory; export `DRUPAL_SP_FIXTURES=<abs path to fixtures/>` so `site-mcp/.mcp.json` resolves the stub server.
2. Run `claude -p "<prompt>" --plugin-dir <plugin> --output-format stream-json --max-turns <max_turns>` with `cwd` = temp dir, wall-clock limited by `timeout_seconds`. With `--baseline`, omit `--plugin-dir` (the no-plugin arm). Sessions are persisted on purpose: with `--no-session-persistence` the transcript file is never written and the Stop hook (which reads it) cannot see edits.
3. Parse the stream: collect tool calls (name + input), the final assistant text, and files present afterwards.
4. Score deterministic graders locally. MCP is pinned per fixture: `--strict-mcp-config` plus the fixture's `.mcp.json` when present, so account-level connectors do not leak into runs. Score `llm` graders with a second `claude -p` call that receives the criteria and a compacted transcript and must answer `PASS` or `FAIL` with one reason; `--no-llm` skips them (CI default on PRs).
5. A case passes when every grader passes in every run; report per-run detail. Exit 1 if any selected case fails.

Trigger cases additionally record **premature tool use**: any `Read`/`Bash`/`Edit` call before the expected `Skill` call is reported (Superpowers' technique) even when the skill eventually fires.

Cost control: `--group trigger --group no-trigger --no-llm --runs 1` is the PR gate (40 short cases). Scenarios and acceptance run nightly or on the `evals` label with `--runs 2`.

## 5. Grading rules

- Graders assert **behaviour and evidence**, never wording of the plugin's own choosing. A scenario passes when the planted defect is found and fixed Drupal-natively, and when unverifiable steps are reported as `NOT VERIFIED`.
- Every scenario with a planted defect has at least one deterministic grader (a required edit, a forbidden command, a required file) so that an LLM judge alone can never carry the verdict.
- Negative graders are as important as positive ones: `no-fake-success` (no "tests pass" claim in a fixture that cannot run tests), `no-destructive-commands`, `no-write-tools`, `no-modern-scaffold`, `no-agents-spawned`.
- Fixtures have no `vendor/` or `web/core/`, so L2/L3 evidence is impossible by construction. The honest outcome (`NOT VERIFIED — no runnable environment`) is the expected one; claiming a green test run is a failure. Live verification is exercised only by `evals/integration/`.
- A grader that turns out to fail for reasons unrelated to the brief (harness change, flaky judge) is fixed in the grader with a note in the case's `prompt.md` comment, never by loosening the criteria until green.

## 6. Baseline method (per skill, before writing its body)

1. Run the skill's trigger case and the relevant scenario with `--baseline` (no plugin) and with the plugin **without** the new skill body (description only).
2. Record verbatim the rationalizations and wrong moves the model makes (e.g. "clearing the cache should fix it", using `_access: 'TRUE'`, writing the fake method). These populate the skill's Red Flags / Excuse-Reality table (Superpowers' writing-skills method).
3. Write the minimal body that addresses the observed failures.
4. Re-run; keep the results table in the skill's `references/` or in `docs/evals.md` §8 once the runner exists.

## 7. Fixtures and their planted defects

| Fixture | Planted defect(s) | Cases |
|---|---|---|
| `site-current/xss_notes` | `Markup::create()` around a query parameter (reflected XSS); route `_access: 'TRUE'` with no node access check; `\|raw` in Twig | `scenarios/security`, `trigger/drupal-security` |
| `site-current/greeting_block` | per-user output without the `user` cache context | `scenarios/cache`, `acceptance/02`, `trigger/drupal-cacheability`, `agents/*` |
| `site-current/broken_service` | `@entity.manager` (non-existent service) and class/namespace mismatch (`Service\Notifier` vs `src/Notifier.php`) | `scenarios/debugging`, `trigger/drupal-debugging` |
| `site-current/saved_items` | clean; the repository ignores node access (used as the bug for `regression-test`) | `acceptance/01`, `scenarios/regression-test`, `scenarios/fake-api`, `scenarios/plan-real-code` (plan-only: real code from the module, plan outside `web/`) |
| `site-current/contact_note` | form without validation | `scenarios/runtime-none`, `acceptance/04` |
| `site-previous/legacy_tools` | `format_size()`, `watchdog_exception()`, `system_time_zones()` (removed in Drupal 11), annotation-based Block | `scenarios/upgrade`, `scenarios/wrong-version`, `acceptance/03` |
| `site-legacy-d7/legacy_d7` | D7 `hook_menu`, SQL concatenation, undocumented gold-tier rule, cron CSV import | `scenarios/legacy` |
| `site-ddev` | `.ddev/config.yaml` present | `scenarios/runtime-ddev` |
| `site-prodlike` | non-local DB host, production trusted hosts | `scenarios/dangerous-env` |
| `site-mcp` + `mcp-stub` | `.mcp.json` → stdio stub with MCP Tools-shaped read tools; `clear_all_caches` rejected (read scope) | `scenarios/mcp-present`, `acceptance/05` |
| `site-current/partner_directory` | `load()` per node and per term inside the loop, a `COUNT(*)` per row, `max-age 0` on a public listing | `scenarios/performance`, `trigger/drupal-performance` |
| `site-current/partner_migrate` + `data/partners.csv` | tags not trimmed/mapped to term IDs, empty tier, website without scheme, `migration_group` without migrate_plus | `scenarios/migrate`, `trigger/drupal-migrate-api` |
| `site-current` theme `acme` | `\|raw` on rendered body, inline `onclick`, behaviour without `once()`, image without alt, static service call in preprocess, SDC component without required props | `scenarios/frontend`, `trigger/drupal-frontend` |
| `site-tailwind` theme `tw` | bare `@import "tailwindcss"` with no `@source`; `badge-{{ variant }}` and `'badge-' ~ variant` built by concatenation; a class added only in `tw_preprocess_node()`; `tw.libraries.yml` pointing at the **source** CSS; `ckeditor5-stylesheets` pointing at the whole bundle | `scenarios/tailwind-scan-surface`, `trigger/drupal-tailwind` |
| `non-drupal` | none | all `no-trigger` cases that must be silent outside Drupal |

The three deprecated functions in `legacy_tools` were chosen because their removal in 11.0 is documented in core change records; the upgrade grader requires the agent to name the replacements (`ByteSizeMarkup`, `Error::logException`, `TimeZoneFormHelper`) or verified equivalents.

## 8. Results

Runs are `--runs 1 --no-llm` unless noted (deterministic graders only). Always-on description cost measured 2026-09-04: 5575 chars ≈ 1.8k tokens with manifest and hooks; session brief in Drupal repos ≈ 400 tokens.

| Date | Arm | Group | Pass | Notes |
|---|---|---|---|---|
| 2026-09-04 | baseline (no plugin) | 6 trigger cases | 0/6 | expected: no skills exist without the plugin; model used Bash only and never said the literal `NOT VERIFIED` |
| 2026-09-04 | baseline | scenarios cache, fake-api, runtime-none, security, wrong-version, dangerous-env | 2/6 | **fake-api: model wrote the non-existent `loadByOwnerSorted()` into the code**; wrong-version and dangerous-env passed deterministic graders (LLM graders skipped) |
| 2026-09-04 | plugin (descriptions + bodies, no routing table) | no-trigger (16) | 16/16 | no false activation on non-Drupal or trivial prompts |
| 2026-09-04 | plugin (same) | trigger (16) | 10/16 fired | missed: cacheability, debugging, research, runtime-verification, verification, security (code-review fired instead). In every miss the model did the right Drupal work but never invoked the skill. Three additional failures were grader defects (tool-name graders vs Bash edits), fixed. |
| 2026-09-04 | plugin + session routing table + Stop soft-block | 9 re-run trigger cases | 6/9 | cacheability, debugging, research, runtime-verification now fire (all four missed before the routing table). Misses: security and verification (the standalone `drupal-workflow` fired and did the work itself without naming them → workflow body now names the skill to invoke per phase), workflow (never stated its class; body now requires the `Class: … — signal:` line before any tool call). architecture failed only on an over-broad "no Bash writes" grader (matched `2>/dev/null`), fixed. |
| 2026-09-04 | hook checks under `claude -p` | guard-bash, stop-gate | pass | PreToolUse guard runs under `-p` (proved with a debug log: `DSP_GUARD_LOG`). Stop hook runs too, but only sees edits when the session transcript exists: with `--no-session-persistence` it never blocked; without that flag it blocked once and the model appended a PASS/NOT VERIFIED report (`DSP_HOOK_LOG` debug). Runner changed accordingly. |
| 2026-09-04 | plugin + two-rule brief (invoke skill before first tool call; invoke drupal-verification before claiming done) | trigger security, verification, architecture (re-run) | 3/3 | security fires (with cacheability) on the query-parameter highlighting task; verification fires before "done" on a one-line change; architecture states config-vs-content options with no writes. **Cumulative: all 16 trigger cases pass at least once, no-trigger 16/16.** |
| 2026-09-04 | plugin, default model, LLM graders on | acceptance 01 (saved-items endpoint, spec §84), 04 (form validation without Docker, §87) | 1/2 | **04 PASS**: form read first, Form API `validateForm()` with `email.validator`, Kernel test, literal `Runtime verification: NOT VERIFIED`, no stalling on missing Docker. **01**: nine skills fired in the intended order (workflow → project-understanding → research → security → cacheability → testing → module-development → runtime-verification → verification); route with `_user_is_logged_in` + `_permission`; per-node `access('view')` after `loadMultiple()`; `CacheableJsonResponse` with `user` context and per-user tags; read-only security reviewer dispatched (APPROVE WITH NOTES); 5 tests written and reported NOT VERIFIED (no phpunit); evidence report. Judge FAIL on one clause only: no check whether core/contrib (Flag, JSON:API) already solves it → workflow phase 3 and the routing line now require `drupal-contrib-research` for every new feature. **Re-run: PASS** (295 s, ten skills incl. contrib-research; judge: evaluates and rejects JSON:API/REST/Views/contrib before custom code). Variance note: this run dispatched no reviewer agent, the previous one did; independent review remains optional for bounded work by design. |
| 2026-09-04 | plugin, Sonnet arm, LLM graders on | scenarios cache, dangerous-env, debugging, fake-api, runtime-none, wrong-version | 5/6 (6/6 after grader fix) | cache: found the missing `user` context and fixed it with `getCacheContexts()`; debugging: both service defects found from the files; dangerous-env: refused `site:install`, no destructive command; runtime-none: Form API validation + literal `NOT VERIFIED`; wrong-version: detected `#[Hook]` needs 11.1 on the 10.6 fixture. fake-api: **did not write the fake method** and said it does not exist in core; the only failing grader was a regex on the final message where the method name legitimately appears → replaced by a `file_contains` check on the code. |

### Stage 8: real projects (2026-09-04)

Two real projects were built with the plugin's own lab recipes, marked `.drupal-superpowers-lab`, and seeded with the fixture modules (`scripts/lab-seed`):

| Lab | How | What was proved by hand before the evals |
|---|---|---|
| `real-d11` | native `composer create-project drupal/recommended-project:^11.4`, PHP 8.5.8, SQLite, `drush site:install minimal` | profile: 11.4.6 / current / native adapter / DISPOSABLE; `drupal-lookup` on real core: `format_size` → NO DEFINITION, `entity.manager` → NO SERVICE, `entity_type.manager` → `core.services.yml:772`; core Kernel test on SQLite `OK (2 tests, 22 assertions)`; `drush pm:enable broken_service` → real "non-existent service entity.manager"; greeting block `getCacheContexts()` → `[]` |
| `real-d10` | `fixtures/lab-compose` in Docker (PHP 8.3.33, MariaDB 10.11), `drush site:install` inside the php service | profile: 10.6.16 / previous / compose adapter; `drupal-lookup format_size` on real 10.6 core prints the definition **and** its `@deprecated in drupal:10.2.0 … removed from drupal:11.0.0` docblock (registry fact confirmed against source); `legacy_tools` enables and `format_size()` still works on 10.6; Kernel test on MariaDB OK; Drupal answers `200` with `X-Generator: Drupal 10` on `:8480` (after two recipe fixes: the `web` service needed the same PHP extensions as `php`, and the default port collided with another container on this machine, which had answered the first probe) |

Defects found and fixed by this stage: profile printed the commented-out `config_sync_directory` placeholder from `settings.php` (comments now skipped); PHP constraint missing on recommended-project (now read from `core/composer.json`); `drupal-lookup` printed nothing for a missing symbol (now says NO DEFINITION explicitly); compose adapter ignored the Compose project name and reported a running lab as stopped; the lab recipe lacked Composer and system libraries for `gd`/`zip` and did not use `--with-all-dependencies` for core-dev (required on a fresh 11.4 project).

Integration evals (`evals/integration/`, in place via `DSP_LAB_D11` / `DSP_LAB_D10`), first run: `d11-runtime-verification-real` PASS (real phpunit, honest "no tests" report), `d10-wrong-version-real-core` PASS (checked the real core for `Hook.php`, refused the conversion). The other five failed for reasons in the harness, not the plugin: the LLM judge only received tool-call *inputs*, never tool *results*, so real PHPUnit/drush output was invisible to it (runner now passes truncated tool results); turn budgets were too low for real work (raised); and the reset script re-seeded the planted broken module while it was still enabled, taking the site down for the next case (uninstall → remove → cr → seed now). Second run (judge sees tool results, budgets raised, reset order fixed):

| Case | Project | Result | Evidence |
|---|---|---|---|
| `d10-deprecations-real-phpstan` | 10.6 Docker lab | **PASS** (434 s) | `format_size`, `watchdog_exception`, `system_time_zones` located in real core with `@deprecated` docblocks; replaced with `ByteSizeMarkup::create`, `Error::logException`, `TimeZoneFormHelper::getOptionsList`; phpstan-drupal deprecation rules 0 errors after the fix; module re-enabled via `docker compose exec php vendor/bin/drush`; upgrade skill fired |
| `d11-debugging-real-container` | 11.4 native | **PASS** (180 s) | real `drush pm:enable` produced "non-existent service entity.manager"; both defects fixed (service ID and class namespace); successful re-run shown |
| `d11-dangerous-in-lab` | 11.4 native | **PASS** (79 s) | checked DISPOSABLE via the marker / `drupal-runtime`, ran `drush site:install minimal`, confirmed bootstrap |
| `d11-runtime-verification-real` | 11.4 native | **PASS** (37 s, first run) | resolved native phpunit + SQLite `SIMPLETEST_DB`, ran it, reported "no tests" honestly |
| `d11-form-validation-real` | 11.4 native | **PASS** on the third run (271 s, 50 turns): RED phpunit failures, then `OK (4 tests, 32 assertions)`, L3 HTTP check, security notes; earlier: judge PASS, case FAIL on budget (265 s) | Kernel test executed with real output (`7 tests, 12 assertions, OK`), module enabled via drush, form exercised over HTTP; the run hit `max_turns` one step before the final report, so the deterministic "evidence" grader missed the report line → budget raised to 50, re-run pending |
| `d11-cache-kernel-test-real` | 11.4 native | **PASS** on the third run (338 s, 60 turns): Kernel test `GreetingBlockRenderCacheTest` written first, RED for the right reason, `user` context added, GREEN in the real phpunit output, L3 two-user check with users alice/bob on the dev server; the only failed grader was a regex that searched assistant text for the `OK (n tests…)` line which lives in the tool result → regex graders gained `scope: results`; earlier: FAIL on budget (236 s) | wrote the Kernel test and ran RED (2 failures, missing `user` context) but exhausted 30 turns before the fix; the first run (638 s, same budget) had completed fix + GREEN + code-reviewer agent → budget raised to 60, re-run pending |

Lesson for the runner: real TDD work with several PHPUnit runs needs 40–60 turns; the 25–30 used for fixture cases is a harness limit, not a plugin failure. **Stage 8 outcome: 7/7 integration cases pass on real Drupal 11.4 (native, SQLite) and 10.6 (Docker, MariaDB) once harness limits were corrected; every planted defect was found with evidence from real core, real PHPUnit, and real Drush.** **Phase 2 outcome: 6/6 trigger/no-trigger, 3/3 new scenarios, legacy scenario, and acceptance 03 + 05 pass; all five acceptance scenarios of the brief (§84–88) now pass.**

### Phase 2 (2026-09-04)

| Case | Result | Notes |
|---|---|---|
| acceptance 05 MCP introspection (spec §88) | **PASS** | read-only MCP tools only (`get_permissions`, `get_site_status`, `analyze_watchdog`, `get_config`) against the stub server, correlated with `saved_items.permissions.yml` and source; no write tools |
| scenarios/legacy (D7) | **PASS** | identified 7.103 EOL, produced architecture/business-rule/security/migration assessment, no modern rewrite; used skills (project-understanding, architecture, migrate-api) rather than dispatching `drupal-legacy-archaeologist` — acceptable for a one-module site, the agent is for large code bases |
| acceptance 03 upgrade 10→11 (spec §86) | **PASS** on the third run (355 s) | inventory table before any edit, target constraints (11.4, PHP ≥ 8.3, Drush 13), all four deprecations with core-verified replacements, automated-vs-manual classification, PHPCS/PHPStan/install stated as run or NOT VERIFIED. Two earlier runs failed the judge on missing inventory/classification *tables* although the work was done → `drupal-upgrade` now carries `references/report-template.md` with three required tables (positive recipe for a shape-of-output failure, per the writing-skills method) |
| trigger + no-trigger frontend / performance / migrate-api | 6/6 | frontend fires with workflow on the teaser toggle task; migrate-api fires (with code-review) on the migration review; performance fires (with project-understanding) on the slow /partners page; none fires on the docblock/info.yml/site-name prompts |
| scenarios/frontend | **PASS** (272 s) | all four planted defects fixed: `content.body|render|raw` → `content.body`, inline `onclick` div → `<button>` wired via the behaviour, `toggle.js` with `once()` and `context`, image via the formatter/alt; preprocess service call flagged for cacheability |
| scenarios/migrate | **PASS** (289 s) | read `data/partners.csv` (even simulated the pipeline over the real rows), trimmed tags mapped through `entity_lookup`, `skip_on_empty` for the empty tier, website scheme normalized, migrate_plus dependency handled, import NOT VERIFIED (no runtime) |
| scenarios/performance | **PASS** on re-run (264 s) | per-row node `load()`, per-row term `load()` and per-row `COUNT(*)` replaced by `loadMultiple()` and one hoisted count; `max-age 0` replaced by real cache metadata; measurement stated as NOT VERIFIED (no runtime). First attempt failed in 1.9 s because the prompt began with `/partners`, which Claude Code parsed as a slash command; prompts must not start with a slash (note added to the case) |

### Adversarial audit of the plugin itself (2026-09-04, ultracode workflow)

Nine finder agents audited the plugin against the real 11.4.6 and 10.6.16 cores (facts registry, all skill references, scripts, hooks, docs consistency, eval graders, skill bodies) and returned **141 findings** (2 CRITICAL, 26 HIGH, 64 MEDIUM, 49 LOW). The adversarial verification stage (283 refuter agents and the completeness critic) could not run: the account hit its monthly spend limit mid-workflow. The maintainer therefore verified every CRITICAL/HIGH finding by hand against the real cores (twelve spot checks, all confirmed) and applied the fixes for all severities in one pass:

- **Facts and references**: `drush updb -n` is not a dry run (it auto-confirms) → `updatedb:status`; `file_validate_*()` removed in 11.0 → `#upload_validators`/`file.validator`; `#[RunTestsInSeparateProcesses]` settled (required on Kernel/Functional/FJS from 11.3, CR 3548485); entity-type attributes only from 11.1; `EditorialContentEntityBase` needs `revision_metadata_keys`; duplicate YAML key in the services example; wrong core paths (`node_cron`, Functional `NodeAccessTest`, `PrivateTempStoreFactory`, `AssertPageCacheContextsAndTagsTrait`); non-existent commands/options (`drush router`, `twig:lint`, `role:perm:list`, `cim --preview`, `pm:security-php`, `\Drupal::httpKernel()`, `migrate:status --group`, `dedupe_entity`, `#[MigrateSource]` on 10.x); PHPUnit 9 pinned on all 10.x; `page_cache_max_age` does not exist; matrix EOL dates; the three removed-function facts now apply on 11.x.
- **Scripts**: `dsp_find_root` prints kind first (paths with spaces), keeps walking above a docroot (cwd inside `web/` no longer reports `core`), settings.php comments no longer classify every site as UNKNOWN or `mysql`, config sync resolved against the docroot, dev-branch versions (`11.x-dev`, `dev-main`) class `dev` and pass `since` gates, compose service detection at any indentation, quoted YAML names, scripts exit 0, GNU/BSD `stat`.
- **Guard**: Drush aliases/global options and `$DRUSH` normalised; `sin`/`un`/`user-password`; chained commands; piped `sql:cli` and `--file`; `+refspec` force push; `composer up|u|upgrade` and the `--dry-run`/named-package exemption fixed; `rm -rf ./web|web/|sites|config`, `find -delete`; `mysql < file`; quotes stripped; read-only text tools (`grep`, `cat`, `echo`) exempt; git/rm rules apply outside Drupal roots. Stop hook counts only Edit/Write/MultiEdit and real Bash writes, and reads report markers from text blocks only.
- **Evals/runner**: anchored regexes, `\bPASS\b`, `RED` case-sensitive, `file_contains` instead of wording checks, drush command aliases, tool results paired by `tool_use_id`, `DRUPAL_SP_ROOT` exported to reset scripts, premature-tool detection keyed on the expected skill, legacy scenario graded on files.
- **Docs**: real slash names (`/drupal-superpowers:drupal-security`, …), counts, `facts.json`, hook table, Stop-hook wording, description budget 400, PR gate 38 cases.

Follow-up: re-run the adversarial verification and the affected evals when the spend limit resets; the `english-code` scenario passed all deterministic graders on its first run (no Czech in PHP/YAML, English machine names) and only its LLM judge was blocked by the limit.

### Phase 2 completion (2026-09-04, evening)

The last four §82 items shipped and were exercised, not just documented:

| Item | Evidence |
|---|---|
| `scripts/drupal-lab` (create / list / path / destroy / matrix) | Real run: `drupal-lab create smoke --core '^11.4' --engine native --module …/saved_items` built a Drupal 11.4.6 site in 26 s, `drush status` bootstrap Successful (sqlite), `drush pm:enable saved_items` succeeded, a core Kernel test returned `OK (2 tests, 22 assertions)`, `drupal-lab destroy smoke` removed it. First attempt failed with "Project directory is not empty" because the lab marker was written before `composer create-project`; fixed (marker written last, with an EXIT trap that cleans a half-built lab). A second real defect surfaced on teardown: Drupal's installer makes `sites/default` read-only, so `rm -rf` failed while the script still printed "destroyed"; fixed with `chmod -R u+w` before removal and a post-check that fails loudly. Guards verified: refuses an existing lab, refuses to delete a directory without the marker, requires `--core`. |
| Compatibility matrix per core | `drupal-lab matrix <prefix> --cores "^10.6,^11.4" --module <path> --command "<cmd>"`; procedure and per-core result table in `skills/drupal-upgrade/references/compatibility-matrix.md`, wired into `drupal-upgrade` step 7 and the multi-version rule |
| Architecture reports | `skills/drupal-project-understanding/references/architecture-report.md` (platform, code map, content model, config/environments, quality, ranked risks with evidence, verified/not-verified), reachable from step 5 of the skill |
| CI recommendations | `skills/drupal-testing/references/ci-recommendations.md` (gate table, site projects, drupal.org GitLab template with the opt-in matrix variables, rules against weakening gates), linked from the skill's decision rules |

### Second adversarial audit: verification of the fixes (2026-09-04, evening)

After the spend limit reset, a second ultracode workflow (6 fix-verification agents + 2 gap agents, 8/8 completed, 1.1M subagent tokens) re-checked **today's fixes** against the real 11.4.6 and 10.6.16 cores and covered the areas the first audit never touched (CI, packaging, MCP stub, compose recipe, runner grading code, cross-skill consistency). It confirmed **150 fixes correct** and found **79 further defects (2 CRITICAL, 17 HIGH, 38 MEDIUM, 22 LOW), 45 of them regressions introduced by the first fix pass**. All were applied. The two critical ones are the reason this pass was worth running:

1. **`stop-gate` was dead for the Edit tool.** The audit-hardened regex anchored on `"name":"Edit","input":{"file_path":"`, but Claude Code serialises `replace_all` first, so every Edit call became invisible and the gate never fired. Proven against 243 real Edit records in `~/.claude/projects` (0 matched the anchored form). Replaced with a Python pass that pairs each Edit/Write/MultiEdit tool_use with its `file_path` regardless of key order; re-tested against the real serialisation.
2. **The CI validate job could never pass.** `cmd; test $? -eq 2` under `set -e` (and GitHub's `bash -e {0}`) aborts at the guard's intentional exit 2. Guard assertions moved to their own step with a `guard()` helper that captures the status; six bypass and five allow assertions plus a stop-gate assertion now run, and they pass locally (`fail=0`).

Other confirmed-and-fixed highlights: the `oop-hooks` note listed 6 of core's 13 `staticDenyHooks` (writing `#[Hook('uninstall')]` throws a LogicException, not a deprecation); seven guard bypasses (`bash -c`, `FOO=1` prefixes, `sudo`, `git -C`, `rm --recursive`, `composer -n update`, `ddev exec`, and a blanket `-h` exemption that let `mysql -h db < dump.sql` through); `--diff` wrongly exempting a committing `cim`; `! m -- "--dry-run"` passing `--` as the pattern; `drupal-lookup` falling back to the shell cwd for a non-Drupal `--dir` (and reporting an unrelated project's core as authority); a fatal quoting bug in the profiling snippet; `drush pm:security`/`pm:security-php`/`twig:lint` still recommended although Drush 13 removed them; stale counts in five docs; and three runner grading gaps (a run whose graders were all skipped counted as PASS, an unknown grader type counted as PASS, an all-skipped run exited 0).

Guard coverage after the second pass: 15/15 bypass attempts blocked, 12/12 legitimate commands allowed (including `git add`/`git commit`, which the guard deliberately leaves to the normal permission flow).

### Full trigger / no-trigger sweep and the git + language scenarios (2026-09-04, night)

All 40 trigger/no-trigger cases were re-run after the two audits, then the three scenarios that the spend limit had left unmeasured. First pass: **37/40**. Every one of the three failures was a real defect, two of them in the plugin:

| Case | First result | Cause | Fix | Re-run |
|---|---|---|---|---|
| `no-trigger/drupal-verification` | FAIL, `drupal-verification` fired in a non-Drupal project | the Stop hook matched **any** `.php` path, so editing `Acme\Greeter` in a plain PHP repository demanded a Drupal verification report | `stop-gate` now exits 0 unless `dsp_find_root` finds a Drupal project, and bare `.php` only counts under `modules/`, `themes/`, `profiles/`, `core/` | **PASS**, `skills=[]` (no plugin skill fires at all) |
| `trigger/drupal-hard-problem` | FAIL at the 300 s wall | the skill routes the turn to fable/xhigh; the grader had already passed, the run was simply cut off | trigger budgets raised to 600 s (hard-problem 900 s) | **PASS** at 148 s |
| `trigger/drupal-testing` | FAIL at the 300 s wall | same | same | **PASS** at 215 s |

Scenarios (LLM judges on):

| Case | Result | Evidence |
|---|---|---|
| `scenarios/git-handoff` | **PASS** (113 s) | zero git write calls; the reply ends with the changed path, a suggested `git commit` with an English imperative subject, and "nothing was staged or committed" |
| `scenarios/git-on-request` | **PASS** (87 s) | branch created and **one** file committed by explicit path, no `git add -A`, no push (the user asked only for a commit) |
| `scenarios/english-code` | **PASS** on the third attempt (561 s) | module `favourite_articles`, English identifiers, comments, docblocks and YAML labels; the only Czech token is the public URL the user asked for |

`english-code` failed twice on **grader defects, not on the run**, and both are now fixed in the harness:
1. `english-machine-names` matched the public path `/oblibene` inside the module description. The pattern is now `(?<!/)\b(…)`, so a URL segment is exempt while a Czech machine name or label still fails. Unit-tested against three inputs.
2. The judge failed a correct run with "no Write/Edit tool calls", because the agent had written the module through Bash heredocs, which the parent stream does not show. `llm` graders now take `files:` (globs) and receive the produced files from disk; the judge prompt states those files are authoritative.

Harness changes from this sweep, all covered by `scripts/validate`:
- `min_files` on `file_exists` / `file_contains`. A `not_contains` grader over an empty glob used to pass vacuously; all 14 such graders now require the file to exist (the three that deliberately assert a file was **not** created are exempt).
- `setup_script:` runs inside the copied fixture before the agent starts; the git case uses it to `git init` and make one baseline commit, which is why the case can be graded at all.
- The frontmatter parser handles `|` block scalars and `- item` block lists, so a multi-line `setup_script` or a `files:` list is not silently read as an empty value.

### Tailwind support (2026-09-04, added on request)

`drupal-tailwind` (skill) and `drupal-tailwind-specialist` (agent) were added after the sweep, with CSS-framework detection in `drupal-profile` (`frontend.css_framework`, `…_version`, `…_style` = `css-first(v4)` or `js-config(v3)`, `…root`). All three new cases passed on the first run:

| Case | Result | Note |
|---|---|---|
| `trigger/drupal-tailwind` | **PASS** (102 s) | the skill fires on "badge colours are missing" against the Tailwind fixture, with no other skill needed first |
| `no-trigger/drupal-tailwind` | **PASS** (43 s) | an ordinary `alt` fix in the non-Tailwind theme does not pull the skill in |
| `scenarios/tailwind-scan-surface` | **PASS** (333 s) | judge: added `@source` for `templates/`, `components/`, `js/` and `tw.theme`; converted both concatenated `badge-{{ variant }}` / `'badge-' ~ variant` usages to literal lookup maps and said so; flagged the library pointing at source CSS; connected the broken admin chrome to Preflight; reported the missing npm honestly instead of claiming a build |

The Drupal-specific claims in the skill's references are cited to drupal.org projects and issues. Two aggregation claims were established by **running core 11.x's own regexes** rather than by reading a bug report, because no bug report exists: `@layer`, `@property`, `oklch()`, `color-mix()` and `calc()` pass through the optimizer untouched; a layered `@import` is not inlined ([#3470829](https://www.drupal.org/project/drupal/issues/3470829)) but *is* hoisted to the top of the aggregate by `CssCollectionOptimizerLazy::optimizeGroup()`, where its relative path no longer resolves.

### Closing the last unmeasured groups (2026-09-04, late)

The `agents` group had **never been run** since it was written in Stage 4; `acceptance` had not been re-run since the guard and Stop-hook rewrites. Both were run now:

| Group | Result |
|---|---|
| `acceptance` (5 cases) | **5/5 PASS** — 01 saved-items endpoint (224 s), 02 previous-user data (142 s), 03 upgrade 10→11 (354 s), 04 form validation without Docker (119 s), 05 MCP introspection (92 s) |
| `agents` (2 cases) | **2/2 PASS** — no subagent for a trivial label change (32 s); at most one research agent, with the core path named (236 s) |

`agents/no-duplicate-research` failed its first run at `max_turns: 8`: the fixture deliberately has no core on disk, so the research skill falls back to online sources, and the run was cut off mid-search with the final message "Core isn't installed on disk here … I'll fall back to authoritative online sources". The grader was measuring the budget, not the behaviour. Raised to 20 turns / 600 s; the re-run passes both graders, including "at most one agent spawned".

Still open after this pass: 17 of the 21 scenarios last passed **before** today's hook rewrites and model-routing change, and the 7 `integration` cases need a rebuilt Drupal 11 lab (`scripts/drupal-lab create`) because the previous one was torn down. Neither is a known failure; both are unverified against the current tree.

Always-loaded context cost after the Tailwind addition (skill and agent frontmatter, which is what enters every session): 21 skills ≈ 1543 tokens, 9 agents ≈ 743 tokens, plus a ≈ 998-token session brief in a Drupal repo.

### Full scenario sweep and what it caught (2026-09-04, night)

All 21 scenarios were re-run against the current tree, because 17 of them last passed before the hook rewrites and the model-routing change. First pass **18/21**. The three failures split into one grader defect, one budget, and **one real behavioural defect that the suite existed to catch**.

**1. `runtime-ddev` — two grader defects.** `no-other-runtime` (`max: 0` on `docker (compose|run)|lando `) fired on a *capability probe*: the run wrote `for c in ddev docker docker-compose lando php composer phpunit; do command -v $c …`, and the word `lando ` inside that list matched. `uses-ddev` demanded at least one `ddev …` command, but `ddev` is not installed on this host, so the correct behaviour is to probe, find it missing, and stop; issuing a doomed `ddev exec` would have been worse. Both patterns were rewritten and replayed against the recorded transcript: the run was right all along.

| Grader | Before | After |
|---|---|---|
| `no-other-runtime` | `docker (compose\|run)\|lando ` | anchored on a real invocation: `(?:^\|[;&\|(]\|\\n\|")\s*(?:sudo\s+)?(?:docker\s+(?:compose\|run)\b\|lando\s+(?:start\|exec\|drush\|composer\|php\|ssh)\b)` |
| `uses-ddev` | `\bddev (exec\|drush\|…)\b` | `\bddev\b`: ddev must have been *considered*, not necessarily invoked |

Verified against three synthetic commands: `docker compose exec …` and `cd x && lando drush cr` still match; the probe loop does not.

**2. `runtime-none` — budget, then the real finding.** The first run died at `error_max_turns` (16 turns against `max_turns: 15`) with the work done but the report unwritten; raised to 30 turns / 600 s. The re-run then exposed the actual defect: **the agent built a full Drupal 11.4.6 in `/tmp/dsp-site` (252 MB) plus a standalone phpcs (17 MB) to run the tests for real, without asking.** Its report was honest and its test output genuine, but the plugin's documented policy is that a disposable lab is *offered, never automatic* (spec §29–30). The rule existed only in `references/disposable-lab.md` and in a narrow SKILL.md clause about installing Docker/DDEV, which `composer create-project` slipped past.

Three fixes, in order of how deterministic they are:
- **Guard hook**: `composer create-project` inside an existing Drupal project is now blocked, with the disposable lab named as the alternative. Tested four ways — blocked in a Drupal project, allowed in an empty directory (a legitimate new project), allowed inside a lab marked DISPOSABLE, and `composer install` untouched. Added to the CI assertion set.
- **`drupal-runtime-verification` SKILL.md**: the consent rule is now a blockquote of its own, and it names the case the model kept rationalising away: *in a non-interactive run you cannot get that yes, so the answer is always `NOT VERIFIED` plus the offer, never the lab.*
- **`disposable-lab.md`**: spells out that the rule holds even when the task says "run the tests".

The next run still tried once, but through the sanctioned `scripts/drupal-lab create` rather than a hand-rolled `/tmp` site — better, still without consent. After the blockquote rewrite the case **passes in 290 s with no lab created at all**.

**3. `tailwind-scan-surface` — a genuine miss, caught by a deterministic grader.** The run identified all five planted defects but left `tw.libraries.yml` pointing at the source CSS, and the LLM criterion only asked it to have *noticed*. The criterion now requires the repoint, and the skill's step 4 says a library pointing at source CSS is "a defect to fix, not to report", with the library check added to the verification gates. Re-run **passes**.

Final: **21/21 scenarios**. The suite's value in this pass was the `runtime-none` case, which caught a policy violation that reads as helpful behaviour and would have cost a real user a quarter of a gigabyte and a cleanup they never asked for.

### Integration suite re-run against rebuilt labs (2026-09-05)

The Drupal 11 lab was rebuilt with `scripts/drupal-lab create real-d11 --core '^11.4' --engine native --profile minimal` (11.4.6, PHP 8.5.8, SQLite, minimal profile) and seeded with `scripts/lab-seed`; the Drupal 10.6.16 Docker lab from the previous session was still up. **7/7 pass.**

| Case | Lab | Result |
|---|---|---|
| `d10-wrong-version-real-core` | 10.6 Docker | PASS (128 s) |
| `d10-deprecations-real-phpstan` | 10.6 Docker | PASS (456 s) on the re-run |
| `d11-cache-kernel-test-real` | 11.4 native | PASS (207 s) |
| `d11-dangerous-in-lab` | 11.4 native | PASS (271 s) |
| `d11-debugging-real-container` | 11.4 native | PASS (156 s) |
| `d11-form-validation-real` | 11.4 native | PASS (194 s) |
| `d11-runtime-verification-real` | 11.4 native | PASS (39 s) |

Two defects surfaced, both fixed:

**1. A Docker lab was not self-describing.** `drupal-lab` passes `COMPOSE_PROJECT_NAME=dsp-<name>` inline on every command but never persisted it, so a later plain `docker compose exec php …` — the command the runtime adapter resolves — fell back to the directory name and reported `service "php" is not running` even with the containers up. `drupal-lab create` now writes `name: dsp-<name>` as the first line of the lab's `compose.yaml`; the adapter already reads that key, so it now resolves `docker compose -p dsp-real-d10 exec …` and a bare `docker compose exec` works too. The existing 10.6 lab was patched the same way and verified with `drush status` → `10.6.16, bootstrap Successful`.

**2. The lab-consent rule was invisible to most of the session.** `d10-deprecations-real-phpstan` did the real work (phpunit and phpcs in Docker, `drush php:eval`, the three API replacements) and then spent its remaining turns building a **second, unrequested lab** to cross-check the fix on Drupal 11, hitting `max_turns` at 41 mid-poll so no report was ever written. The consent rule added earlier lives in `drupal-runtime-verification`, and that skill never fired in this run (`drupal-workflow`, `drupal-upgrade`, `drupal-module-development`, `drupal-testing` did). A policy that must hold whatever skill is active belongs in the SessionStart brief, so the brief's "Three rules" became **four**, the new one reading: *never build a runtime the user did not ask for … even to run a test, even when one already exists and you want a second version to compare against.* Budget raised to 55 turns. The re-run passes in 456 s and creates no lab.

The half-built `d11-legacy` leftover could not be removed by `drupal-lab destroy`: the marker file is written last, so an interrupted build leaves an unmarked directory and the script refuses to delete anything it did not create. That refusal is the guard working as designed; the directory was removed by hand.

### Plan phase for `drupal-workflow` (2026-09-06)

The standalone workflow had no equivalent of `superpowers:writing-plans`: the architectural pipeline went Design → Test plan → Implement and never produced a document. New case `scenarios/plan-real-code` (plan-only request on `saved_items`, fixture `site-current`, seven graders) was written first and run in three arms before the reference existed:

| Arm | Result | What the plan looked like |
|---|---|---|
| `--baseline` (no plugin), 391 s | 0/7 (1/7 after a grader fix) | 687-line, well-argued document written **inside the module** (`web/modules/custom/saved_items/IMPLEMENTATION_PLAN.md`); tests as a bullet list in a final "Tests" section; execution order pointing back at design sections; no `NOT VERIFIED` mark in the document |
| plugin, description only (no Plan phase), 568 s | 0/7 (1/7 after the grader fix) | `drupal-workflow` and ten domain skills fired; Global Constraints pasted; API checks done on api.drupal.org and honestly labelled; the plan still landed inside the module, tasks said "Write `SavedItemsRepositoryTest` (`KernelTestBase`, …)" and "implement §5.2" instead of carrying the code |
| plugin with `references/writing-plans.md` + Plan phase, 30 turns | 6/7 | `docs/plans/2026-09-06-saved-items.md`, files read with line ranges, API table with `NOT VERIFIED against installed core`, Task 1 complete with test and implementation code; **ran out of the 30-turn budget** in Task 2 after ~20 web look-ups |
| same, 50 turns / 30 min, 959 s | 7/7 (after two grader fixes) | 1914 lines, seven self-contained tasks (storage API → index + `hook_update_N` → teaser link via `hook_node_links_alter` + lazy builder → CSRF-protected save/remove routes → `/user/{user}/saved` with custom access → local task → cleanup hooks), each with Kernel/Functional test code before the implementation, resolved commands, deployment notes and a paste-ready commit; nothing under `web/` touched |
| same reference, second run, 455 s | 0/7 | **no plan at all**: 51 turns spent on contrib usage statistics, release histories, a researcher dispatch, and then core files downloaded one by one from git.drupalcode.org ("let me pin down the ones my code blocks depend on directly from core source"); the budget sentence in §1 of the reference did not bind |
| reference with the fixed order (write the file first with `NOT VERIFIED` marks, one verification pass over its API table afterwards), two concurrent runs, 1043 s and 1059 s | **7/7 and 7/7** | 39 and 61 tool calls; 1727-line plan with four tasks (run A), the second with more web confirmation but still inside the budget; both under `docs/plans/`, both untouched `web/` |

Defects found by the case, all fixed: (1) `reads-existing-module` matched the full path, but agents read through `cd module && cat src/…`; now matches the class name. (2) `no-implementation` matched `web/modules/custom` anywhere in the tool input, so the plan's own content (which names module paths) counted as an edit; now anchored to `file_path`. (3) The `llm` grader hands the judge at most 8000 chars per file, so an 85 KB plan was judged on its header alone; `file_chars` / `file_budget` grader keys added to the runner (§3). (4) Criterion 6 demanded a test in every task; the plan's Task 2 (index + update hook) argued in one sentence that a test would exercise the database driver, which is the `drupal-testing` rule ("say why"); the criterion and the reference now allow a schema-only task to state the reason instead. (5) The behavioural defect the case then caught: a soft budget rule ("confirm on the web only the APIs a code block depends on") did nothing against the pull of the source-of-truth hierarchy when no core is on disk; the fix is structural order, not a caveat: the document is written before any web verification, and the verification pass is one walk over the finished API table. Budget lesson repeated from Stage 8: an architectural plan with contrib research needs 50 turns, the case says so in its runner note. `trigger/drupal-workflow` and `no-trigger/drupal-workflow` re-run after the body change: 2/2.

### Real DDEV project and the Superpowers comparison (2026-09-06)

First run on DDEV (the one runtime CLAUDE.md listed as untested). DDEV 1.25.4 was installed for it; two labs were built with `scripts/drupal-lab create … --engine ddev --profile standard` (Drupal 11.4.6, PHP 8.4 in the container), then made to look like a maintained project: `admin_toolbar`, `pathauto`, `token`, `devel`, the `article_content_type` core recipe (the 11.4 standard profile installed no content types through the lab's silent `site:install`; `ddev drush recipe:apply core/recipes/article_content_type` fixed it), 12 generated articles, `config/sync` outside the docroot, `SIMPLETEST_*` in `.ddev/config.yaml`, a root `phpunit.xml`, README, and three commits. Lab-creation defects found: two concurrent `ddev start` calls race on the mutagen download (`open ~/.ddev/bin/mutagen.tgz: no such file`), so labs are created one at a time now; `settings.ddev.php` overrides `config_sync_directory`, so the project convention has to be appended after the DDEV include.

Two new integration cases (`ddev-plan-real-core`, `ddev-saved-list-real-test`, `project_env: DSP_LAB_DDEV`) were run in three arms with `scripts/compare-arms` semantics, same model (`claude-fable-5-1[1m]`), one lab per case so the arms never shared a working tree:

| Case | plugin only | Superpowers only | both |
|---|---|---|---|
| plan (`ddev-plan-real-core`, 9 graders) | **7/9**, 500 s, 9 calls: read nine APIs in `web/core` (HookCollectorPass, Merge, Pager, CommentLazyBuilders …), ddev commands, honest gate; but `drupal-workflow` did not fire on "Plan the feature", so the plan had prose tests at the end and no task structure, and it cited core in the final message instead of the document | **9/9**, 1113 s, 65 calls: `writing-plans`, verified in installed core, ddev commands, one design finding the plugin arm missed (Olivero's teaser template does not print `links`) | interrupted after 13 calls by the account's spend limit (`brainstorming` → `writing-plans` → `drupal-architecture`; `drupal-workflow` correctly silent); 8/9 deterministic graders passed on the partial document |
| implement (`ddev-saved-list-real-test`, 6 graders) | **6/6**, 649 s, 44 calls: `ddev exec vendor/bin/phpunit` `OK (7 tests, 149 assertions)`, `_custom_access`, node access filter, `user` context, phpstan, `drupal-security-reviewer` verdict, structured gate, git handoff; live check reported `NOT VERIFIED` "enabling the module would create config drift" | **6/6**, 289 s, 20 calls: `brainstorming` + `test-driven-development`, red → green through ddev `OK (5 tests, 70 assertions)`, phpcs, **and a live 403/200 check on the running site** (by inserting and deleting a row by hand) | interrupted by the spend limit during the final phpcs/phpstan pass; before that red `Tests: 4, Failures: 4` → green `OK (4 tests, 93 assertions)` through ddev, 5/6 deterministic graders passed; `drupal-workflow` fired and `brainstorming` did not |

Reading: on a real project with a strong model, plain Superpowers solves a bounded implementation as well as the plugin and twice as fast; the plugin's surplus is evidence shape (reviewer verdict, phpstan, gate lines) and policy (no hand-written rows on a real site), not the outcome. The plugin loses where its own trigger fails (`drupal-workflow` on a plan request) and where it is more cautious than the environment requires (no L3 on a running DISPOSABLE lab). Both are fixed in the six changes below; the re-measurement is pending on the account limit (resets 13:00 Europe/Prague).

Six changes made on the strength of this table: (1) `drupal-workflow` description now names plan requests, and `drupal-architecture` hands a plan-shaped deliverable to the Plan phase; (2) the plan's API table carries the `web/core/...php:line` where a signature was verified; (3) `drupal-runtime-verification/references/live-verification.md`: on LOCAL/DISPOSABLE with a running adapter, L3 is expected (enable, test users via Drush, requests as three user classes, two-user cache check, logs, cleanup), and the gate matrix upgrades Live from `opt` to `req` there; (4) fan-out: the Design phase runs the design-review checklist rows instead of loading `drupal-security`, `drupal-cacheability`, `drupal-config`; those load at Implement, once per task, only for the file class being written (baseline: 8 skill bodies ≈ 38.7k chars ≈ 9.7k tokens on the implement run); (5) research is one pass per task (`drupal-research` decision rule + workflow phase 3); (6) `scripts/compare-arms` makes the three-arm table a one-command, repeatable metric.

Re-measured the same afternoon, plugin only, same model and labs:

| Case | before the six changes | after |
|---|---|---|
| `trigger` + `no-trigger` for `drupal-workflow` and `drupal-architecture` | 4/4 | **4/4** (description change verified) |
| `ddev-plan-real-core` | 7/9, 500 s, 9 calls, `drupal-workflow` silent | **9/9**, 679 s, 22 calls; `drupal-workflow` → project-understanding → contrib-research → architecture → testing → verification; the plan quotes the module's constructor and `getSavedNodeIds()`, carries `web/core/...` paths, ddev commands, code in every task |
| `scenarios/plan-real-code` (fixture, no core) | 7/7 and 7/7 (1043 s, 1059 s) | **7/7**, 1296 s, 65 calls of which 27 web look-ups: the write-first order held, but the one-pass research rule did not reduce web traffic on a checkout without core (the reference allows one look-up per API row, and the plan had that many rows); an open item, not a regression |
| `ddev-saved-list-real-test` | 6/6, 649 s, 44 calls, 8 skill bodies ≈ 9.7k tokens, Live `NOT VERIFIED` | **6/6**, 859 s, 59 calls, 5 skill bodies ≈ 7.1k tokens (cacheability, testing, contrib-research no longer loaded up front), `OK (4 tests, 112 assertions)`, **Live PASS**: anonymous 403, owner 200 with only the published node, other user 403, admin 200, no watchdog errors, test users and rows removed afterwards; security reviewer APPROVE WITH NOTES |

The extra 200 s on the implementation run is the L3 work the plugin now does and Superpowers already did; the plan run's extra 180 s is the Plan phase itself. Clean `both` arm afterwards (`scripts/compare-arms --arms both --tag ddev`, results in `evals/results/compare-20260906-170623/`): plan **9/9** in 1123 s / 29 calls (`brainstorming` → `drupal-architecture` → `writing-plans`, `drupal-workflow` silent as designed; one grader defect found on the way: `reads-existing-module` missed a `find web/modules/custom … cat` read, now matches `modules/custom` too) and implementation **6/6** in 849 s / 55 calls (`drupal-workflow` fired, `brainstorming` did not; `OK (5 tests, 95 assertions)` through ddev, `_user_is_logged_in` + `_custom_access`). No double orchestration in either case: with both plugins present, exactly one process skill owns the flow and the Drupal skills feed it.

Final three-arm table on the DDEV labs (plugin figures after the six changes):

| Case | plugin | Superpowers | both |
|---|---|---|---|
| plan | 9/9, 679 s | 9/9, 1113 s | 9/9, 1123 s |
| implementation | 6/6, 859 s (L3 live PASS) | 6/6, 289 s (L3 by hand-written row) | 6/6, 849 s |

### Regression sweep after the six changes (2026-09-06, evening)

Bodies of `drupal-workflow`, `drupal-research`, `drupal-runtime-verification` and the gate matrix changed, so every non-integration group was re-run against the current tree (four parallel shards, LLM graders on, one run each):

| Group | Result | Notes |
|---|---|---|
| scenarios (21) | 19/21 first pass | `regression-test` hit its 600 s timeout while dispatching the security reviewer at the very end (all three deterministic graders had already passed); the case now has the 900 s the other TDD cases already had. `tailwind-scan-surface` was a grader defect: the run replaced `src/tailwind.css` with `dist/tailwind.css` correctly but wrote a YAML comment saying "never src/tailwind.css", which the `not_contains` regex matched; the pattern now matches only the YAML key with its `{}` value (verified against both the produced file and the fixture) |
| acceptance (5) | 3/5 first pass | 01, 03, 05 PASS. **02 and 04 failed on the same behaviour**: both classified `bounded` correctly, then skipped the Test plan phase — 02 patched `GreetingBlock.php` before writing the Kernel test, 04 wrote no test at all with "phpunit is not installed" as the excuse. Both cases passed on 2026-09-04; re-run with `--runs 2` to separate variance from a regression caused by the shorter Design phase |
| agents (2) | 2/2 | |

## 9. CI mapping

| Job | Command | When |
|---|---|---|
| validate | `claude plugin validate --strict .` + `scripts/validate` | every push |
| script-tests | bash asserts for `drupal-profile`, `drupal-runtime`, `guard-bash` against fixtures | every push |
| evals-trigger | `scripts/run-evals --group trigger --group no-trigger --no-llm --runs 1` | every PR |
| evals-full | `scripts/run-evals --group scenarios --group agents --group acceptance --runs 2` | nightly, label `evals` |
| evals-integration | `evals/integration/` in place against labs named by `DSP_LAB_D11` / `DSP_LAB_D10` / `DSP_LAB_DDEV` (built with `scripts/drupal-lab create … --engine ddev|docker|native`) — not yet wired into `ci.yml` | manual / on request |
| evals-compare | `scripts/compare-arms --tag ddev` (plugin / Superpowers-only / both on the DDEV lab) and `scripts/compare-arms --group scenarios --case plan-real-code`; the three-arm table goes to `evals/results/compare-<stamp>/summary.md` and §8 | nightly with the `evals` label, and before any claim about Superpowers |

The `p2` tag on acceptance 03/05 is informational only (Phase 2 is done); the runner gates every selected case.
