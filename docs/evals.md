# Evals

Stage 4 deliverable. The eval suite exists **before** any SKILL.md so that every skill's first version is measured against a baseline run without the plugin (spec §66–68, D9). Inventory as of 2026-09-04: **53 cases, 101 graders**, 8 fixtures.

## 1. Why evals come first

Skill descriptions are trigger surfaces; skill bodies shape behaviour. Neither can be judged by reading. The suite gives three signals per change: did the right skill fire (trigger), did the wrong one stay silent (no-trigger), and did the outcome meet the brief (scenarios, acceptance). A description or body may only change together with its before/after results.

## 2. Layout

```
evals/
├── trigger/<skill>/              16 cases: the prompt MUST activate <skill>
├── no-trigger/<skill>/           16 cases: the prompt MUST NOT activate <skill>
├── scenarios/<name>/             14 cases from spec §66
├── agents/<name>/                 2 cases from spec §68
├── acceptance/<nn>-<name>/        5 cases from spec §84–88 (03 and 05 tagged p2)
├── integration/                   in-place cases against a real project named by env DSP_LAB_D11 (Stage 8)
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
| `file_exists` | `path` (glob, relative to the temp cwd) | at least one file matches after the run |
| `file_contains` | `path` (glob), `pattern` (regex), `match: contains \| not_contains` | the on-disk file content (not) matching after the run; preferred over tool-name graders because agents may edit via Bash |
| `llm` | body = criteria | a judge model answers PASS given transcript + criteria |
| `tool_order` | `before`, `after` | reserved for Stage 5 cases (test written before implementation edit) |

Skill activation is observed as a `Skill` tool call whose input matches `(drupal-superpowers:)?<skill>\b`. Trigger cases require `min: 1`; no-trigger cases require `max: 0`.

The `fixture:` key is ours. Integration cases use `project_env: DSP_LAB_D11` (run in place in that directory, no copy) and `reset_script` (run before and after each run; re-seeds the fixture modules with `scripts/lab-seed` and uninstalls them). The `fixture:` key is ignored for those. When the native `claude plugin eval` becomes available on this account, a `scaffold_script` shim will copy the same fixture; nothing else in the format needs to change.

## 4. Runner (`scripts/run-evals`, Stage 5)

```
scripts/run-evals [--group trigger|no-trigger|scenarios|agents|acceptance] [--case <name>] [--tag <tag>]
                  [--runs N] [--no-llm] [--baseline] [--plugin-dir .] [--json out.json]
```

Per case and run:
1. Copy `fixtures/<fixture>` to a fresh temp directory; export `DRUPAL_SP_FIXTURES=<abs path to fixtures/>` so `site-mcp/.mcp.json` resolves the stub server.
2. Run `claude -p "<prompt>" --plugin-dir <plugin> --output-format stream-json --max-turns <max_turns>` with `cwd` = temp dir, wall-clock limited by `timeout_seconds`. With `--baseline`, omit `--plugin-dir` (the no-plugin arm). Sessions are persisted on purpose: with `--no-session-persistence` the transcript file is never written and the Stop hook (which reads it) cannot see edits.
3. Parse the stream: collect tool calls (name + input), the final assistant text, and files present afterwards.
4. Score deterministic graders locally. MCP is pinned per fixture: `--strict-mcp-config` plus the fixture's `.mcp.json` when present, so account-level connectors do not leak into runs. Score `llm` graders with a second `claude -p` call that receives the criteria and a compacted transcript and must answer `PASS` or `FAIL` with one reason; `--no-llm` skips them (CI default on PRs).
5. A case passes when every grader passes in every run; report per-run detail. Exit 1 if any selected case fails.

Trigger cases additionally record **premature tool use**: any `Read`/`Bash`/`Edit` call before the expected `Skill` call is reported (Superpowers' technique) even when the skill eventually fires.

Cost control: `--group trigger --group no-trigger --no-llm --runs 1` is the PR gate (32 short cases). Scenarios and acceptance run nightly or on the `evals` label with `--runs 2`.

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
| `site-current/saved_items` | clean; the repository ignores node access (used as the bug for `regression-test`) | `acceptance/01`, `scenarios/regression-test`, `scenarios/fake-api` |
| `site-current/contact_note` | form without validation | `scenarios/runtime-none`, `acceptance/04` |
| `site-previous/legacy_tools` | `format_size()`, `watchdog_exception()`, `system_time_zones()` (removed in Drupal 11), annotation-based Block | `scenarios/upgrade`, `scenarios/wrong-version`, `acceptance/03` |
| `site-legacy-d7/legacy_d7` | D7 `hook_menu`, SQL concatenation, undocumented gold-tier rule, cron CSV import | `scenarios/legacy` |
| `site-ddev` | `.ddev/config.yaml` present | `scenarios/runtime-ddev` |
| `site-prodlike` | non-local DB host, production trusted hosts | `scenarios/dangerous-env` |
| `site-mcp` + `mcp-stub` | `.mcp.json` → stdio stub with MCP Tools-shaped read tools; `clear_all_caches` rejected (read scope) | `scenarios/mcp-present`, `acceptance/05` |
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

## 9. CI mapping

| Job | Command | When |
|---|---|---|
| validate | `claude plugin validate --strict .` + `scripts/validate` | every push |
| script-tests | bash asserts for `drupal-profile`, `drupal-runtime`, `guard-bash` against fixtures | every push |
| evals-trigger | `scripts/run-evals --group trigger --group no-trigger --no-llm --runs 1` | every PR |
| evals-full | `scripts/run-evals --group scenarios --group agents --group acceptance --runs 2` | nightly, label `evals` |
| evals-integration | Docker Compose Drupal from `fixtures/lab-compose/`, `evals/integration/` | nightly, label `integration` |

Cases tagged `p2` are excluded from pass/fail gating until Phase 2 but still run nightly for information.
