# Ecosystem analysis

Stage 1 deliverable for Drupal Superpowers (see `docs/spec.md` §3, §91). Research date: **2026-09-04**. Everything below was verified against live sources on that date unless marked *(unverified)*. Raw research notes are not part of the repo; this document is the distilled result.

Sections: 1 Claude Code plugin platform · 2 Superpowers · 3 Drupal skill collections · 4 Canonical Drupal guidance and toolchain · 5 Drupal MCP ecosystem · 6 Reusable ideas · 7 Things to integrate · 8 Things not to copy · 9 Architectural decisions · 10 Open items and unverified facts.

---

## 1. Claude Code plugin platform (target: Claude Code 2.1.x, tested with 2.1.260)

Facts that constrain the design:

| Topic | Finding | Consequence |
|---|---|---|
| Manifest | `.claude-plugin/plugin.json` needs only `name`. Optional: `version`, `description`, `author`, `license`, `keywords`, `dependencies` (semver constraints on other plugins), `userConfig`, `defaultEnabled`. Only the manifest lives in `.claude-plugin/`; every other component is auto-discovered at the plugin root: `skills/`, `agents/`, `commands/` (legacy flat `.md`), `hooks/hooks.json`, `.mcp.json`, `.lsp.json`, `monitors/monitors.json`. | The spec's proposed layout is valid as-is. No `skills`/`agents` keys needed in the manifest. |
| Skills | Frontmatter supports `name`, `description` (capped at 1536 chars incl. `when_to_use`), `disable-model-invocation`, `user-invocable`, `allowed-tools`, `disallowed-tools`, `model`, `effort`, `context: fork` + `agent`, `paths` (globs gating auto-activation), `hooks`, `metadata`, `license`, `compatibility`. Bodies lazy-load on invocation and then persist in context for the session. `${CLAUDE_SKILL_DIR}`, `${CLAUDE_PLUGIN_ROOT}`, `${CLAUDE_PROJECT_DIR}` substitute in bodies. | `user-invocable: true` skills replace a separate `commands/` directory for `/drupal-superpowers:audit` style entry points. `paths:` lets Drupal-file-specific skills stay silent in non-Drupal repos. `context: fork` gives isolated, verbose-safe execution without defining an agent. |
| Agents | `agents/*.md` frontmatter: `name`, `description`, `tools`, `disallowedTools`, `model`, `permissionMode`, `skills` (preload), `memory`, `isolation: worktree`, `maxTurns`. Plugin agents sit at the bottom of the scope hierarchy (managed > CLI > project > user > plugin). | Read-only agents are expressible via `tools:` allowlists. Projects can override our agents. |
| Hooks | Events: SessionStart, UserPromptSubmit, PreToolUse, PermissionRequest, PostToolUse, PostToolUseFailure, Stop, SubagentStart/Stop, FileChanged, CwdChanged, ConfigChange, and others. Handler types: `command`, `http`, `mcp_tool`, `prompt`, `agent`. Exit code 2 blocks on blocking events; JSON `hookSpecificOutput` can `allow`/`deny`, add `additionalContext`, or rewrite `updatedInput`. | Deterministic guardrails (destructive Drush/SQL/git detection) belong in a PreToolUse `command` hook on `Bash`. Completion-gate support fits a Stop hook that only speaks when Drupal files changed. |
| MCP | `.mcp.json` supports stdio/http/ws with `${VAR}` and `${VAR:-default}` expansion. Plugin server tools are named `mcp__plugin_<plugin>_<server>__<tool>`. **There is no runtime API to ask "is tool X available"; the model's tool list is the only signal.** | MCP capability detection must be a *skill instruction* ("look at your tool list for these fingerprints"), not code. A plugin-level `.mcp.json` applies to every project the user opens, which is wrong for a server that needs a specific site root. |
| LSP | `.lsp.json` can register any language server, including intelephense or phpactor for `.php`; the binary must already be installed. Invalid configs are silently skipped. | Ship an opt-in `.lsp.json` example, not an active one. |
| Plugin interop | No API to detect another installed plugin. Skills may reference another plugin's skills by `other-plugin:skill`. `dependencies` can declare a required plugin. | Superpowers must remain optional: detect it by skill presence in the model's skill list, not via `dependencies`. |
| Evals | `claude plugin eval` reads `evals/**/case.yaml` or `prompt.md` + `graders/*.md`; graders: `regex`, `tool_used`, `tool_order`, `file_exists`, `llm`, `baseline`; `mocks/` for MCP; with/without-plugin ablation. **On this account it prints "currently in early access" and exits 1.** | Author cases in the native format from day one, but ship a fallback runner (`claude -p --plugin-dir … --output-format stream-json`) that evaluates the deterministic grader subset so CI works today. |
| Validation | `claude plugin validate --strict <path>` checks structure, frontmatter, agents, hooks, path traversal. `claude plugin details <name>` reports projected token cost. `claude plugin tag` creates `name--vX.Y.Z` tags. | Both go into CI. |
| Context cost | At session start only manifest metadata, skill descriptions, agent descriptions, hook config, and MCP tool lists load. | Keep descriptions tight; every always-on token is paid on every session of every project. |

---

## 2. Superpowers 6.3.0 (obra/superpowers, MIT)

Analyzed from the locally installed copy at `~/.claude/plugins/cache/claude-plugins-official/superpowers/6.3.0/`.

**Architecture.** 14 skills, 3377 SKILL.md lines total, one `SessionStart` hook, **no `agents/` and no `commands/`**. Subagents are dispatched at runtime from markdown prompt templates stored next to skills (`implementer-prompt.md`, `task-reviewer-prompt.md`, `code-reviewer.md`). The hook injects the whole `using-superpowers` skill wrapped in `<EXTREMELY_IMPORTANT>` on startup, `/clear`, and after compaction. That injection is the entire integration.

**Process chain.** `using-superpowers` → `brainstorming` (classifies work as spike / bounded / architectural, hard gate on user approval) → `writing-plans` → `subagent-driven-development` or `executing-plans` → `requesting-code-review` → `finishing-a-development-branch`. `systematic-debugging` → `test-driven-development` → `verification-before-completion` run cross-cutting. Skills reference each other by `superpowers:<name>` with `REQUIRED SUB-SKILL` markers.

**Explicit extension points relevant to us.**
- Skill Priority rule in `using-superpowers`: "process skills come first, then implementation skills (frontend-design, etc.)". "Fix this bug → systematic-debugging first, then domain skills." This already orders Drupal skills after Superpowers process skills, provided our descriptions read as *domain* skills.
- Brainstorming's terminal states are path-bound: after an architectural brainstorm, the only permitted next skill is `writing-plans`. A Drupal architecture skill therefore has to be consulted *inside* brainstorming and planning, not after.
- `<SUBAGENT-STOP>`: subagents ignore the skill discipline entirely. Implementers and reviewers see only their brief. Drupal rules reach them exclusively through spec → plan "Global Constraints" → task brief → reviewer's `[GLOBAL_CONSTRAINTS]` slot.
- Precedent for optional third-party skills: brainstorming says "Use elements-of-style:writing-clearly-and-concisely skill if available".
- Their CLAUDE.md: "Skills for specific domains … belong in their own standalone plugin." A Drupal companion is exactly what they expect.

**Toolchain blind spots.** Worktree setup detects only package.json / Cargo.toml / requirements.txt / pyproject / go.mod; branch finishing runs only `npm test / cargo test / pytest / go test`. No composer, no ddev/lando, no phpunit, no drush.

**Testing.** In-tree `tests/` drive `claude -p --plugin-dir . --max-turns 3 --output-format stream-json` and grep for `"skill":"(ns:)?<name>"` plus premature tool use. Behavioural evals live in an external repo and are not in CI. Skill edits require before/after eval evidence.

**Writing-skills rules that will govern our authoring.** Description = third-person "Use when <moment>, before <guarded action>", trigger conditions only, never a workflow summary (they measured that a summary makes the agent skip the body). Body target under 500 words, heavy reference in supporting files, one runnable example, flowcharts only for non-obvious decisions. New skills require a baseline failure observed *without* the skill first. Match form to failure: prohibition + rationalization table for discipline skills, positive recipe for shape-of-output problems, clarity only (no persuasion) for reference skills.

---

## 3. Drupal skill collections and Claude Code projects

| Project | Type | License | Activity | Skills / agents / hooks | Version handling | Runtime | Evals |
|---|---|---|---|---|---|---|---|
| [grasmash/drupal-claude-skills](https://github.com/grasmash/drupal-claude-skills) | agentskills collection + `.claude/agents` | MIT | 76★, push 2026-07-30, 1 contributor | 14 capability skills (avg 11.7 KB, max 31 KB) / 8 gate agents / `permissions.ask` only | static "D10 & D11" | `ddev drush` hard-coded; good PHPUnit + DTT skill | none |
| [drupaltools/skills](https://github.com/drupaltools/skills) | agentskills collection + agents | **GPL-2.0** | 10★, push 2026-07-14, 1 contributor | 31 micro/task skills (a third are business templates) / 10 persona agents | static, inconsistent | `.ddev/config.yaml` sniff | none |
| [drupal/ai_best_practices](https://www.drupal.org/project/ai_best_practices) | Composer plugin syncing agentskills into `.agents/skills/` + managed AGENTS.md | **GPL-2.0-or-later** | 1.0.x-dev, commit 2026-08-21, **20 maintainers** (webchick, dries, e0ipso, grasmash, phenaproxima…), no tagged release | 8 skills: accessibility (+4 sub), automated-testing, configuration, expert-corrections, gitlab, render-pipeline, skill-authoring, writing-documentation | inline dated facts ("hard requirement in 11.3") | mandates DDEV + `SIMPLETEST_*`; no detection | **yes**: dataset.yaml + rubrics.yaml + static-checks.json, Inspect AI in GitLab CI |
| `drupal/ai_skills`, `drupal/surge` | predecessors | GPL | deprecated → ai_best_practices | — | — | — | — |
| Drupal AI module `.agents/skills/` | module-shipped micro skills | GPL | active | 6 `create-*` skills + a PHP verify script each | module-specific | — | deterministic verify scripts |
| [gkastanis/drupal-workflow](https://github.com/gkastanis/drupal-workflow) | **real Claude Code plugin** (plugin.json + marketplace.json) | MIT in manifest, **no LICENSE file** | 15★, inactive since 2026-04-15 | 19 skills / 4 agents / 10 commands / 13 hook entries | frontmatter `drupal-version`, `last-reviewed`; version injected at SubagentStart | DDEV/Lando/native `$DRUSH` resolution; testing skill replaces PHPUnit with `drush eval` + curl | `claude -p` string assertions, tuned until 315/315 pass |
| [edutrul/drupal-ai](https://github.com/edutrul/drupal-ai) (d.o `ai_code_guardrails`) | copy-in `.claude/` folder | MIT | 71★, push 2026-06-05 | 40 topic skills / 10 agents / 8 always-on rules / 2 hooks | D11-only | DDEV boot in session hook | none |
| [madsnorgaard/drupal-agent-resources](https://github.com/madsnorgaard/drupal-agent-resources) | `.claude/` resources via `agr` CLI | README-only MIT | 46★, push 2026-04-22 | 6 skills incl. a 39 KB `drupal-expert` monolith | static, **wrong facts** (hook attributes "D10.3+", "procedural deprecated in D11") | Drush-first | structure CI only |
| [gxleano/drupal-agentic-workflow](https://github.com/gxleano/drupal-agentic-workflow) | Composer dev-dep + setup script | **none** | 3★, push 2026-07-30 | ~6–20 skills / 3 shell hooks | **dynamic**: `detect.mjs` → `stack.json`, per-version knowledge dir, live `site-api.json` via Drush | ddev → vendor/bin → PATH resolver; post-write phpcbf/phpcs hook via exit 2 | tooling tests only |
| [ablerz/claude-skill-drupal-module](https://github.com/ablerz/claude-skill-drupal-module) | single skill via Composer | GPL-2.0-or-later | 8★, push 2026-05-05 | 1 skill + 13 references | **per-version branches** + monthly Action diffing change records against `last_updated` | bespoke `ddev phpcs/phpunit` commands | none |
| [siva01c/claude-plugins](https://github.com/siva01c/claude-plugins) | Claude Code marketplace (7 plugins) | MIT | 16★, push 2026-07-10 | thin Drupal skills (1–1.4 KB) | D11 static | `ddev drush` | none |
| [trebormc/drupal-ai-agents](https://github.com/trebormc/drupal-ai-agents) | DDEV add-on workspace | Apache-2.0 | 65 commits *(push date unverified)* | 34 skills / 11 agents / 15 rules | static | DDEV-native, Playwright/Behat | none seen |
| [Omedia/drupal-skill](https://github.com/Omedia/drupal-skill) | marketplace (3 plugins) | unclear | single commit 2025-10-19, abandoned | 3 | "D8–11" | — | none |
| [drupal-canvas/skills](https://github.com/drupal-canvas/skills) | agentskills | MIT | push 2026-08-19 | 14 Canvas-only skills with short rubric references | Canvas-specific | — | none |
| [ivangrynenko/cursorrules](https://github.com/ivangrynenko/cursorrules) | Cursor `.mdc` | MIT | stale 2025-10 | ~10 Drupal OWASP rules | — | — | none |
| DDEV | no official AI add-on; only community add-ons that run Claude inside the container | — | — | — | — | — | — |

**Cross-cutting observations.**
1. agentskills-format collections dominate; genuine Claude Code plugins are rare (gkastanis, siva01c, Omedia) and all single-author.
2. Version handling is almost universally static prose, and the errors cluster in the same places: `#[Hook]` (11.1+), `#[LegacyHook]`, procedural hooks (not deprecated), Symfony/PHPUnit majors, PHP minimums. Nobody states "check `composer show drupal/core` before asserting a version-gated fact" as a rule.
3. DDEV is assumed everywhere; Lando appears once; bare PHP never. PHPUnit guidance is thin or replaced by curl smoke tests.
4. Only ai_best_practices and gkastanis have evals; the AI module's deterministic verify scripts are the most reliable pattern seen.
5. Context cost is ignored: 15–40 KB skill bodies, always-on rules, multi-hook session starts.
6. Licensing: MIT (grasmash, edutrul, siva01c, canvas, ivangrynenko), Apache-2.0 (trebormc), GPL (drupaltools, ablerz, every drupal.org project), none (gxleano, madsnorgaard by file, gkastanis by file). Derivative content already circulates between MIT repos without attribution.
7. Bus factor: every GitHub repo is one person; only ai_best_practices has a maintainer group.

---

## 4. Canonical Drupal guidance, toolchain, and support matrix

### 4.1 Guidance sources
- **ai_best_practices** is the community-canonical home; drupal.org's "AI Coding Tools for Drupal Development" guide (3 pages, updated Apr 2026) points to it and ships **no official AGENTS.md/CLAUDE.md template**. Skill style there is procedural, opinionated, links to canonical docs, and pairs every skill with evals. Gaps as of today: no coding-standards, DI, security, hooks/plugins, or entity-API skill (open issues exist). It is GPL: **ideas and facts can be re-derived; text cannot be copied.**
- **Coding standards** moved to https://project.pages.drupalcode.org/coding_standards/ (drupal.org standards pages are marked obsolete). Policy: new code follows current standards regardless of core version.
- **Drupal Code Query** (Théodore Biadala, https://api.tresbien.tech/v1/) is a read-only, unauthenticated JSON API over daily static analysis of core and all contrib: change records with impacted symbols and projects, core symbol lifecycle (`/v1/symbol/search`, `/v1/symbol/{id}` with added/deprecated/removed stamps), regex code search across core + contrib, `POST /v1/composer/scan` for major-version readiness. A hosted MCP server was announced 2026-08-20 *(endpoint unverified)*. Third-party and rate-limited, so it is an accelerator, never a dependency.
- **drupal.org REST (`api-d7`)** is verified and usable without auth: `node.json?type=changenotice&field_project=3060&field_change_to_branch=11.4.x`, single `node/<nid>.json`, `type=sa` for advisories, `field_project_machine_name=<name>` to resolve projects. Exact-match filters, 50 per page, "use respectfully". RSS at `changes/drupal/rss.xml`.
- **api.drupal.org has no JSON endpoint.** Search redirects to the HTML page on exact match; URL pattern `/api/drupal/<url-encoded file path>/(function|class|interface)/<name>/<branch>` is stable. Installed core source remains the top authority, which matches spec §12.

### 4.2 Toolchain versions (Packagist, 2026-09-04)

| Package | Version | Notes |
|---|---|---|
| drupal/coder | 9.0.1 | PHPCS 4, Slevomat; JS sniffs removed (use ESLint); several sniffs renamed, old `phpcs.xml` may need updates |
| mglaman/phpstan-drupal | 2.1.2 | PHPStan ^2.1; 1.x supported until Drupal 10 EOL |
| mglaman/phpstan-drupal-deprecations | 0.11.1 (2019) | **dead**; use `phpstan/phpstan-deprecation-rules` |
| mglaman/drupal-check | 1.5.0 (2024) | legacy; prefer phpstan-drupal or Upgrade Status |
| palantirnet/drupal-rector | 1.1.3 | Rector ^2; covers 10.0→11.4 deprecations and hook-to-OOP |
| drupal/upgrade_status | 5.0.0-alpha3 | core ^10.4 ‖ ^11 ‖ ^12; 10→11 and 11→12 checks |
| drush/drush | 13.7.6 stable | Drupal 10.4–11.x, PHP ≥ 8.3; **14.x-dev** targets Drupal 11.3+/12, no stable tag *(unverified beyond Packagist)*; Drush 12 = Drupal 10.0–10.x |

### 4.3 Release and support matrix (snapshot, will go stale)

```yaml
verified_against: drupal.org release cycle + release notes
last_reviewed: 2026-09-04
```

| Branch | Status | PHP |
|---|---|---|
| 10.5.x | EOL | 8.1–8.4 |
| 10.6.x | security-only; **Drupal 10 EOL 2026-12-09** | 8.1–8.4 |
| 11.3.x | security-only until 12.0.0 window (week of 2026-12-07) | 8.3–8.5 |
| 11.4.x | **current stable** (11.4.0 on 2026-07-01, latest 11.4.6), supported to June 2027 | 8.3–8.5 |
| 12.0.x | **12.0.0-alpha1 on 2026-09-02**; beta1 week of 2026-09-14; GA week of 2026-12-07; requires ≥ 11.4 to upgrade | **8.5 only** |

Drupal 12 platform: Symfony 8.1, MySQL 8.0 / MariaDB 10.11 / SQLite 3.45, PostgreSQL 18 or 19 *(sources disagree)*. Many core modules removed to contrib (Ban, Contact, Field Layout, History, Settings Tray, Shortcut, Telephone, Migrate Drupal, Stable 9; Search/Toolbar/Claro/Olivero proposed). Core development now happens on `main`.

Version-gated API facts the router must own (each verified against a change record):
- `#[Hook]` OOP hooks: 11.1.0 (CR 3442349); ordering + preprocess hooks 11.2; theme hooks 11.3 (CR 3551652). Procedural hooks are **not** deprecated yet (plan issue #3481555, removal Drupal 13 at the earliest). Install/update hooks, `hook_theme`, `hook_requirements` stay procedural.
- Plugin attributes: introduced 10.2; attribute class required in 12.0; annotation support removed in 13 (CR 3522776).
- Recipes: 10.3+. `#[RunTestsInSeparateProcesses]`: required from 11.3 *(secondary source)*.

---

## 5. Drupal MCP ecosystem

Convergence: two Drupal-side servers matter, one Node sidecar is the zero-install path, and everything else is niche or vendor-specific.

| Server | What it is | Transport / auth | Read-only surface | Write / danger | State (2026-09-04) |
|---|---|---|---|---|---|
| **MCP Tools** (`drupal/mcp_tools`, code-wheel) | Module + Tool API plugins with own STDIO/HTTP transports | `drush mcp-tools:serve --uid=N --scope=read`; HTTP `POST /_mcp_tools` with API keys + scopes | **Broadest**: site/system status, security updates, cron, watchdog, queues, content types + fields, field types, config get/list/status/**diff/drift**, roles/permissions, menus, files, `mcp_tools_list_available` | 36 submodules incl. content/structure CRUD, cache, recipes, migration; scopes `read`/`write`/`admin`, **new installs default to read**, dev/staging/prod presets, audit log | 1.0.0-beta18 (2026-07-24), core ^10.3 ‖ ^11 ‖ ^12, GPL, not security-covered |
| **MCP Server** (`drupal/mcp_server` 2.x, e0ipso/mglaman, Acquia/Lullabot) | Framework on the official `mcp/sdk`; **ships no tools** | `drush mcp:server` (STDIO, no `--uid`); Streamable HTTP `/mcp` behind new `access mcp server` permission; OAuth 2.1 via `mcp_server_oauth` | Whatever Tool API tools are bridged via `mcp_server_tool_bridge` (wire name `tool_api__<id>`, per-tool `access()`), e.g. Tool Belt entity/field/bundle listing, Project Context Connector snapshot | Depends on bridged tools; `destructiveHint` propagated | 2.0.0-beta2 (2026-09-02); the ecosystem's convergence point (mcp 1.x merging in, Tool API required by AI 2.0) |
| **MCP** (`drupal/mcp` 1.x, Omedia, drupalmcp.io) | JSON-RPC over `POST /mcp/post`, optional Deno sidecar | auth off by default; token/basic → configured user | `info`, `status` (modules/security), `search-content`, `jsonapi_read`/`jsonapi_schema` | whitelisted Drush plugin (off by default), Tool API, AI agents | 1.2.3 (2025-11-14), stable, **security-covered**, being merged into mcp_server |
| **drush-mcp** (`@bloomidea/drush-mcp` npm + composer bridge) | Node sidecar shelling out to Drush over local/DDEV/Lando/SSH/Docker | STDIO; security boundary = shell access | `drupal_status`, `drupal_introspect`, `drupal_field_info`, `drupal_watchdog`, `drupal_config_get`, entity read/list | **no read-only mode**; `drupal_drush`, `drupal_php_eval`, `drupal_sql_query`; bridge commands default to `--user=1` | v0.4.0 (2026-09-02), MIT, 4★, active |
| Project Context Connector | read-only snapshot (versions, modules + security status, themes, config flags) over REST/HMAC/Drush, and as a Tool API tool | basic/OAuth/HMAC | one snapshot | none | 1.2.0 (2026-02-16), GPL |
| Drush Webmaster | **not MCP**: 60+ `wm:*` Drush commands with YAML output + shipped SKILL.md | shell | schema dump, entity query | full CRUD with `--dry-run` | 1.0.0-beta1, GPL, "full creative AND destructive power" |
| Others | miniOrange `mo_mcp_server` (JWT/API key, security-covered), Wilkes-Liberty `drupal-mcp-connector` + `mcp_sentinel` (JSON:API-centric, governance presets), `mcp_core` (niche), DDEV MCPs (`ddev_exec`, SQL whitelist), Upsun hosted MCP (read-only default), Pantheon/Acquia content MCPs, hobby drupal.org MCPs | | | | No official api.drupal.org or change-record MCP exists |

**Detection has to be fingerprint-based.** The `mcp__<server>__` prefix is the user's arbitrary key. Reliable fingerprints: `mcp_tools_list_available` / `get_site_status` (MCP Tools), `tool_api__*` (mcp_server bridge), `info` + `status` + `search-content` (mcp 1.x), `drupal_status` + `drupal_introspect` + `drupal_drush` (drush-mcp), `drupal_mcp_whoami` (connector), `site_info` + `module_status` (miniOrange), `ddev_exec` (DDEV). Secondary filesystem signals: `.mcp.json` commands containing `mcp-tools:serve`, `mcp:server`, `drush-mcp`, `mcp-server-drupal`, `ddev-mcp`; `composer.lock` containing the module names; `drush-mcp.yml`.

**No server exposes routes.** SQL exists only via drush-mcp, DDEV MCPs, or Sentinel's governed command. Config import exists only via Drush wrappers.

---

## 6. Reusable ideas (with provenance)

Ideas are free to reuse; text is reusable only from MIT/Apache sources with attribution.

| Idea | Source | License status |
|---|---|---|
| Trigger-only "Use when <moment>, before <guarded action>" descriptions; Excuse/Reality tables built from *observed* baseline failures; verification gate IDENTIFY→RUN→READ→VERIFY→claim; implementer four-status contract (DONE / DONE_WITH_CONCERNS / BLOCKED / NEEDS_CONTEXT) with report-to-file; reviewer "Do Not Trust the Report" + diff-as-file + "cannot verify from diff" category; Global Constraints slot in plans; `claude -p` trigger tests | Superpowers | MIT, adapt with attribution |
| Skill Priority rule (process first, domain second) as the interop contract | Superpowers | contract, not content |
| `permissions.ask` for destructive Drush; quality-gate / done-gate agent pair; contrib patch discipline (`patches-relock`); testing traps: RED-first, cheapest bootstrap, anonymous-403 trap, vacuous tests | grasmash | MIT, adapt with attribution |
| Procedural skill style that routes and points to canonical sources; `name` + `description` only frontmatter; dataset/rubric eval format; expert-corrections JSONL loop (claim, correction, classification SKILL_GAP / SKILL_STALE / CONFABULATION …); "AI agents consistently pick the wrong storage mechanism" decision tree | ai_best_practices | GPL → **re-derive**, never copy |
| Per-skill deterministic verify script; module-shipped `.agents/skills/` convention | Drupal AI module | GPL → pattern only |
| Runtime resolver chain (ddev → vendor/bin → PATH); `stack.json` from composer.lock + info.yml + `.ddev/config.yaml`; live `site-api.json` (service IDs, bundles, fields, routes, permissions) to kill hallucinated identifiers; post-write phpcbf/phpcs hook returning exit 2 | gxleano | **unlicensed → ideas only** |
| Monthly job diffing drupal.org change records against a `last_reviewed` stamp and opening a maintenance issue | ablerz | GPL → pattern only |
| Hook set as a menu (SessionStart context, PreToolUse sensitive-file block, PostToolUse PHP lint, Stop verification gate, SubagentStart version injection); static-checks.json | gkastanis | no LICENSE file → ideas only |
| Run-tool → fix loops for phpcs/phpstan/phpunit; validator scripts instead of prose checklists | drupaltools | GPL → pattern only |
| Fingerprint-based MCP detection; abstract capability map (`site.status`, `config.diff`, `fields.describe`, …) filled per server; read/routine-write/destructive/shell-equivalent classification; refuse writes to non-local hosts | derived from MCP survey | ours |
| Change-record and symbol-lifecycle lookups via drupal.org REST and Drupal Code Query, with installed source as the tie-breaker | derived | ours |

---

## 7. Things to integrate (not rebuild)

1. **Superpowers process skills** when present: brainstorming, writing-plans, SDD/executing-plans, TDD, systematic-debugging, verification-before-completion, code review. We add Drupal content at their extension points (see §9 D2).
2. **ai_best_practices** as a source-of-truth tier (spec §12 item 2). When a project has `.agents/skills/drupal-*` synced, our skills should point to them for the topics they cover instead of restating them, and our contributions to those topics should go upstream where the GPL is acceptable to the contributor.
3. **Module-shipped skills**: discover `vendor/**/.agents/skills` and `web/modules/**/.agents/skills` and mention them in the Project Capability Profile.
4. **MCP Tools** as the recommended read-only introspection server; **mcp_server 2.x + tool bridge** as the forward-compatible target; **drush-mcp** as the zero-module path treated as shell-equivalent. Templates only, user-installed.
5. **Drupal Code Query API** and **drupal.org REST** for change records, symbol lifecycle, and contrib readiness, wrapped in a script with graceful "endpoint unavailable" handling.
6. **Existing project tooling first**: `phpcs.xml(.dist)`, `phpstan.neon`, composer scripts, `.ddev/`, `.lando.yml`, CI files. The toolchain versions in §4.2 are defaults only when the project has nothing.
7. **Coding-standards site** and change records by link, not by copy.

---

## 8. Things not to copy

1. A second `<EXTREMELY_IMPORTANT>` bootstrap or any "1% chance → MUST" register. Two competing "go first" authorities fight and double the session tax. Descriptions plus Superpowers' priority rule are enough.
2. Duplicate process skills (our own brainstorming/TDD/debugging/verification) when Superpowers is installed. gkastanis did this; it creates two orchestrators.
3. Static version facts in prose ("Drupal 11 supports X"). This is where every surveyed project is wrong today and will be wrong again after 12.0.0 in December.
4. Monolithic skills (39 KB `drupal-expert`, 31 KB contrib-mgmt) and always-on rule files.
5. DDEV as an assumption; bespoke `ddev phpcs` commands the user must install; replacing PHPUnit with `drush eval` + curl.
6. Business templates, vendor-specific content (Acquia, Canvas), and a Drush command encyclopedia in core skills.
7. String-matching evals tuned until green (overfitting), and evals that live only in an external repo needing tmux and API keys.
8. Superpowers' generic toolchain lists and fixed artifact paths; do not fork them, provide Drupal detection as a sibling and reuse their `docs/superpowers/` locations.
9. Network calls or telemetry inside the plugin.
10. GPL text from ai_best_practices, drupaltools, ablerz, or any drupal.org project; unlicensed text from gxleano, madsnorgaard, gkastanis.
11. Drupal Console in any form (EOL since 2020; spec §93).
12. Multi-harness manifest sprawl on day one.

---

## 9. Architectural decisions

Each decision states the choice, the reason, and what it rules out. These feed Stage 2 (`docs/architecture.md`) and are open to revision there.

**D1. A real Claude Code plugin, MIT, Claude Code only for MVP.**
`.claude-plugin/plugin.json` + auto-discovered `skills/`, `agents/`, `hooks/`; `LICENSE` file present from the first commit; `ATTRIBUTION.md` for adapted MIT content. Skills are written action-first ("run the project's test command") so an agentskills export or other harnesses stay possible later, but no `.cursor-plugin` etc. now. Rules out: Composer-plugin distribution, copy-in `.claude/` folders.

**D2. Companion, not orchestrator.**
No bootstrap injection of skill text, no duplicate process skills. Drupal skills are *domain* skills whose descriptions name the design/implementation moment ("Use when designing how a feature should be built on Drupal … during design discussions, before an implementation plan exists"). Each body carries a short interop block: with Superpowers active, consult during brainstorming/planning and copy the constraints into the plan's Global Constraints; standalone, ask the listed clarifying questions first. One `drupal-task-workflow` skill provides the lightweight standalone fallback (orient → understand → design check → test strategy → implement → verify) and explicitly defers to `superpowers:*` skills when they appear in the skill list. A Drupal Global Constraints template and a Drupal reviewer lens for the `[GLOBAL_CONSTRAINTS]` slot are how rules reach Superpowers subagents. Rules out: any dependency declaration on Superpowers.

**D3. Version truth is computed, not remembered.**
`scripts/drupal-profile` (bash, zero deps) reads `composer.lock`, `composer.json`, `*.info.yml`, `.ddev/config.yaml`, `.lando.yml`, docker-compose, `phpunit.xml*`, `phpstan.neon*`, `phpcs.xml*` and emits a JSON Project Capability Profile including the router class (current / previous / EOL / dev). Reference packs contain *how to verify* (which change record, which core file, which API call) plus a single dated `references/versions/matrix.md` with `verified_against` / `last_reviewed` frontmatter. Version-gated facts in skill bodies are limited to the handful in §4.3 and each cites its change record. Rules out: per-version skill copies, undated prose facts.

**D4. Runtime adapter is a script with an environment classification.**
`scripts/drupal-runtime` resolves the wrapper for `drush`, `composer`, `php`, `phpunit`, `npm` (DDEV → Lando → docker compose → project wrapper → native → none) and classifies the environment (DISPOSABLE / LOCAL / DEVELOPMENT / STAGING / UNKNOWN / PRODUCTION) from hostnames, `.ddev`, env vars, and settings files. Every skill that runs anything calls it; PreToolUse guards read its output. Rules out: `ddev drush` hard-coding, requiring users to install custom DDEV commands.

**D5. MCP is optional and detected by fingerprint.**
No active `.mcp.json` in the plugin (it would apply to every project). Ship templates under `docs/` for MCP Tools (read scope default), mcp_server 2.x, and drush-mcp, and a user-invocable `setup-mcp` skill that writes a *project-scoped* `.mcp.json` on request. The `drupal-runtime-verification` and `drupal-project-understanding` skills instruct the model to scan its tool list for the fingerprints in §5, map to abstract capabilities, probe once, and fall back MCP → Drush via Bash → `wm:*` → static config. Shell-equivalent tools (`drupal_drush`, `drupal_php_eval`, `drupal_sql_query`, `ddev_exec`, whitelisted Drush tools) get the same gating as Bash. Rules out: assuming one server implementation.

**D6. Few skills, short bodies, references on demand.**
Target 12–16 capability skills (spec §7), SKILL.md under ~150 lines / 500 words, `references/` for tables, `scripts/` for deterministic checks. `paths:` frontmatter gates Drupal-file skills; `user-invocable` skills provide the `/drupal-superpowers:*` entry points instead of a `commands/` directory. Rules out: micro skills, always-on rules, monoliths.

**D7. Agents only where isolation or permissions pay for themselves.**
MVP: `drupal-researcher` (read-only tools, web + source lookup), `drupal-security-reviewer` (read-only), `drupal-test-engineer` (isolates PHPUnit output, returns findings), `drupal-code-reviewer` (Drupal lens, independent of implementer), `drupal-upgrade-specialist`. `tech-lead`, `legacy-archaeologist`, `frontend-specialist`, `performance-reviewer` are deferred to Stage 3 taxonomy review: when Superpowers is present its SDD controller already plays tech-lead, and the other three may start as `context: fork` skills. Rules out: persona agents that are knowledge folders.

**D8. Hooks are few, fast, deterministic.**
PreToolUse on `Bash`: block or ask on the destructive list (spec §52) unless the runtime classification is DISPOSABLE, exit 2 with a one-line reason. PostToolUse on `Edit|Write` for `*.php|*.module|*.install|*.theme|*.inc`: `php -l` always; targeted `phpcs` only if a resolver finds one in under a second, otherwise a hint. Stop: if Drupal files changed and no verification evidence appeared, inject a short reminder, never block. SessionStart: run `drupal-profile`; inject at most ~10 lines, and only when `drupal/core` is in `composer.lock`. Rules out: full PHPUnit/PHPStan on edit, the Superpowers-style full-skill injection.

**D9. Evals in the native format, run by our own runner until early access lands.**
Author `evals/<case>/prompt.md` + `graders/*.md` from day one (spec §66–68), tagged `trigger`, `no-trigger`, `security`, `cache`, `fake-api`, `version`, `debug`, `regression`, `upgrade`, `legacy`, `runtime-none`, `runtime-ddev`, `mcp-present`, `mcp-absent`, `dangerous-env`. Ship `scripts/run-evals` that executes `claude -p --plugin-dir . --output-format stream-json` and scores the deterministic graders (`regex`, `tool_used`, `tool_order`, `file_exists`), plus an optional LLM grader via `claude -p`. Skill descriptions change only with before/after trigger results. Fixtures are small synthetic Drupal trees; live-Drupal integration evals run in CI via Docker (available here; DDEV is not) and are separate from unit evals. Rules out: waiting for `claude plugin eval`, string-matching until green.

**D10. Source-of-truth plumbing is scripted.**
`scripts/drupal-lookup`: given a symbol or topic, search installed core (`grep`/`find` under the resolved docroot), then Drupal Code Query, then drupal.org change records, and print ranked evidence with URLs. Each backend is optional and reports "unavailable" rather than failing. This is the "show me how core does it" mechanism (spec §13) in one command. Rules out: web-search-first research.

**D11. Contribution and licensing hygiene.**
Nothing is copied from GPL or unlicensed repos. Facts re-derived from ai_best_practices are verified against core or change records before use and cite those, not the skill. Improvements to topics ai_best_practices already covers are offered upstream first.

---

## 10. Open items and unverified facts

To resolve in Stage 2/3 or flag in docs:
- Whether `claude plugin eval` becomes available for this account; until then D9's runner is the CI path.
- Drupal Code Query hosted MCP endpoint (announced 2026-08-20, URL not confirmed) and rate limits under CI load.
- Drush 14 stable date (only `14.x-dev` on Packagist); affects the Drupal 12 fixture.
- Drupal 12 PostgreSQL minimum (18 vs 19 in different sources).
- `#[RunTestsInSeparateProcesses]` "required from 11.3" is from ai_best_practices and secondary sources; verify against the change record before encoding it.
- `drush mcp:server` (mcp_server 2.x) account context and `mcp_server_oauth` scope semantics (alpha).
- Whether `composer require drupal/ai_best_practices:@dev` resolves through the drupal.org facade (not on Packagist); needed only if we recommend it in README.
- Contributor counts and license files for gxleano, gkastanis, Omedia, trebormc (GitHub API rate limits).
- Local machine facts for Stage 8: Docker 29.5 and PHP 8.5.8 present; no DDEV, Lando, Drush, or Playwright on PATH. Disposable fixtures will use Docker Compose here.
