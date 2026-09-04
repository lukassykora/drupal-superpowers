# Architecture

Stage 2 deliverable. Builds on `docs/spec.md` (the brief) and decisions D1–D11 in `docs/ecosystem-analysis.md`. Where this document and the brief differ, this document states why. Status of each part: **MVP** (spec §81), **P2** (spec §82), or **later**.

Contents: 1 Scope · 2 Principles · 3 Plugin layout · 4 A task's life · 5 Core mechanisms · 6 Skills · 7 Agents · 8 Hooks · 9 References and staleness · 10 Evals and CI · 11 Security model · 12 Context budget · 13 Delivery phases · 14 Inputs for Stage 3.

---

## 1. Scope

Drupal Superpowers is a Claude Code plugin that makes Claude work through a Drupal task the way a senior Drupal team would: establish facts about the project, apply the right version's APIs, choose the smallest Drupal-native design, implement to standards, and prove the result with static, automated, and live evidence.

It is a **companion** plugin: standalone it carries a lightweight workflow; next to Superpowers it supplies Drupal intelligence to Superpowers' process skills and never competes with them (D2).

It is **not** a Drupal knowledge base. Knowledge lives in the installed core source, change records, and canonical docs; the plugin ships the machinery to find, rank, and verify it (spec §89).

Out of scope for MVP: frontend/SDC, performance profiling, Migrate API, core-contribution mode, multi-harness packaging.

---

## 2. Principles that shape the structure

| Principle (spec) | Structural consequence |
|---|---|
| Evidence before assumptions (§2) | Two scripts, `drupal-profile` and `drupal-runtime`, produce machine-readable facts before any skill reasons. Skills quote their output, never guess. |
| Version-aware (§2, §10–11) | One router, computed from `composer.lock`; version-gated facts are a small registry with change-record citations, not prose. |
| Minimal context (§6) | SKILL.md ≤ ~150 lines; tables and procedures in `references/`; verbose work in forked skills or agents. |
| Capability-based skills (§7) | 16 skills, each answering "what can Claude now do", each with a trigger moment. |
| Runtime evidence beats reasoning (§2, §31) | Three verification levels are first-class data in the completion report; `NOT VERIFIED` is a legitimate value. |
| Never claim unverified success (§59–61) | The Stop hook and the `drupal-verification` skill both consume the same evidence ledger. |
| Security by default (§2, §19, §52–53) | Environment classification gates destructive commands deterministically (hook), not by prompt. |
| Companion to Superpowers (§4) | No bootstrap injection, no duplicate process skills; interop via descriptions, plan constraints, and a fallback skill. |
| Prevent overengineering (§15, §78, §80) | Complexity classification happens once, early, and selects which phases apply. |

---

## 3. Plugin layout

```
drupal-superpowers/
├── .claude-plugin/
│   ├── plugin.json              name, version, description, author, license, keywords, homepage
│   └── marketplace.json         single-entry dev marketplace for local install
├── skills/
│   ├── drupal-project-understanding/   SKILL.md, references/profile-fields.md
│   ├── drupal-workflow/                SKILL.md, references/global-constraints-template.md, references/plan-task-template.md
│   ├── drupal-research/                SKILL.md, references/source-hierarchy.md, references/core-patterns-index.md
│   ├── drupal-architecture/            SKILL.md, references/decision-tables.md, references/design-review-checklist.md
│   ├── drupal-module-development/      SKILL.md, references/{services,plugins,routing,forms,entities,hooks,config-files}.md
│   ├── drupal-testing/                 SKILL.md, references/{test-levels,phpunit-setup,test-integrity,project-frameworks}.md
│   ├── drupal-debugging/               SKILL.md, references/{drupal-failure-modes,log-sources}.md
│   ├── drupal-security/                SKILL.md, references/{checklist,access-patterns,output-escaping}.md
│   ├── drupal-cacheability/            SKILL.md, references/{metadata-rules,core-examples}.md
│   ├── drupal-config/                  SKILL.md, references/{storage-decision,schema,drift,deployment}.md
│   ├── drupal-contrib-research/        SKILL.md, references/evaluation-criteria.md, scripts/contrib-info
│   ├── drupal-runtime-verification/    SKILL.md, references/{levels,browser,disposable-lab,mcp-capabilities}.md
│   ├── drupal-code-review/             SKILL.md, references/review-lens.md
│   ├── drupal-verification/            SKILL.md, references/gate-matrix.md
│   ├── drupal-upgrade/                 SKILL.md, references/{workflow,tooling,version-jumps}.md
│   └── drupal-setup-mcp/               SKILL.md, references/templates/{mcp-tools,mcp-server,drush-mcp}.json
├── agents/
│   ├── drupal-researcher.md            read-only
│   ├── drupal-security-reviewer.md     read-only
│   ├── drupal-code-reviewer.md         read-only
│   ├── drupal-test-engineer.md         runs tests, returns findings
│   └── drupal-upgrade-specialist.md
├── hooks/
│   ├── hooks.json
│   └── scripts/{session-start,guard-bash,lint-php,stop-gate}
├── scripts/                            zero-dependency bash; JSON via first available of php/python3/node
│   ├── drupal-profile                  → Project Capability Profile JSON
│   ├── drupal-runtime                  → runtime resolution + environment class JSON
│   ├── drupal-lookup                   → ranked evidence for a symbol/topic
│   ├── drupal-facts                    → version-gated fact registry query
│   ├── run-evals                       → eval runner fallback (D9)
│   └── validate                        → CI: plugin validate --strict + link check + frontmatter lint + staleness
├── references/
│   ├── versions/matrix.md              dated support matrix (frontmatter: verified_against, last_reviewed, sources)
│   ├── versions/facts.yaml             version-gated facts registry with change-record URLs
│   ├── security/                       shared with drupal-security references
│   └── patterns/                       "how core does it" index pointing at core file paths per branch
├── evals/                              native case format + fixtures (see §10)
├── fixtures/                           small synthetic Drupal trees (see §10)
├── docs/                               spec, ecosystem-analysis, architecture, security, runtime, evals
├── ATTRIBUTION.md  CONTRIBUTING.md  LICENSE (MIT)  README.md
```

Deviations from the brief's proposed layout (§5) and why:
- No `commands/`: user-invocable skills provide `/drupal-superpowers:<name>` (spec §79) without a second component type.
- No active `.mcp.json` / `.lsp.json` at the root: a plugin-level MCP config applies to every project (D5); LSP configs are opt-in examples under `docs/`.
- `skills/` is flat, not grouped by domain directory: Claude Code discovers `skills/*/SKILL.md`; grouping adds path depth without a discovery benefit.
- `references/` at the root holds only cross-skill data (version matrix, fact registry, core-pattern index). Skill-specific references live with the skill so they load on demand.

---

## 4. A task's life

```
user prompt
  │
  ├─ SessionStart hook (once): drupal-profile → ≤10-line context if a Drupal project is detected
  │
  ├─ skill triggering (descriptions only; nothing else is always-on)
  │     Superpowers present:  brainstorming / systematic-debugging first (their rule) → drupal-* consulted inside
  │     Superpowers absent:   drupal-workflow classifies complexity and picks phases
  │
  ├─ Orient:      drupal-project-understanding (profile + runtime), version router class
  ├─ Understand:  read existing code on the execution path (never edit by filename)
  ├─ Research:    drupal-research → drupal-lookup (installed core → Code Query → change records → docs)
  ├─ Design:      drupal-architecture decision tables + design-review checklist (only relevant rows)
  ├─ Test plan:   drupal-testing picks the cheapest proving layer
  ├─ Implement:   drupal-module-development / drupal-config / … ; PostToolUse lint on PHP edits
  ├─ Verify:      drupal-runtime-verification: L1 static → L2 automated → L3 live; PreToolUse guard on Bash
  ├─ Review:      drupal-code-review / drupal-security-reviewer agent for non-trivial changes
  └─ Gate:        drupal-verification builds the evidence report; Stop hook nags if evidence is missing
```

Small tasks (typo, label, CSS tweak) run Orient → Implement → L1 → Gate with a two-line report. The complexity classification in §5.8 decides which rows apply.

---

## 5. Core mechanisms

### 5.1 Project Capability Profile (`scripts/drupal-profile`) — MVP

Input: a directory (default cwd). Walks up to find the Composer root, then reads: `composer.json`, `composer.lock`, `vendor/composer/installed.json` if present, `web|docroot|html/core/lib/Drupal.php` for `const VERSION`, `*.info.yml` under custom paths, `config/**`, `.ddev/config.yaml`, `.lando.yml`, `docker-compose*.yml`, `phpunit.xml*`, `phpstan.neon*`, `phpcs.xml*`, `.gitlab-ci.yml`, `.github/workflows/*`, `package.json`, `.mcp.json`, `.agents/skills/*`, `vendor/**/.agents/skills/*`.

Output: JSON on stdout, cached in `${CLAUDE_PLUGIN_DATA}/profiles/<sha1 of composer root>.json` with the `composer.lock` mtime; skills call the script, never read the cache directly.

```json
{
  "schema": 1,
  "generated_at": "2026-09-04T10:12:00Z",
  "composer_root": "/path/to/project",
  "docroot": "web",
  "project_kind": "site | custom-module | contrib-module | core | unknown",
  "drupal": { "version": "11.4.6", "major": 11, "minor": 4, "source": "composer.lock | Drupal.php | composer.json-constraint", "distribution": "core | drupal-cms | profile:<name>" },
  "router": { "class": "current | previous | eol | dev", "eol_date": "2027-06-01", "note": "…" },
  "php":   { "constraint": ">=8.3", "local_cli": "8.5.8" },
  "packages": { "drush": "13.7.6", "symfony_http_kernel": "7.3.2", "phpunit": "11.5.1", "coder": null, "phpstan_drupal": "2.1.2", "drupal_rector": null, "upgrade_status": null, "core_dev": true, "composer_patches": "2.0.0" },
  "paths": { "custom_modules": ["web/modules/custom"], "custom_themes": ["web/themes/custom"], "profiles": [], "recipes": ["recipes"], "config_sync": "config/sync", "config_split": true },
  "modules": { "contrib": ["pathauto", "…"], "custom": ["my_module"], "patched": ["drupal/core"] },
  "features": { "multisite": false, "multilingual": true, "content_moderation": true, "workspaces": false, "search": "search_api_solr | search_api_db | core | none", "queue": "core | advancedqueue", "cron": "ultimate_cron | core", "database": "mysql | pgsql | sqlite | unknown" },
  "quality": { "phpcs_config": "phpcs.xml.dist", "phpstan_config": "phpstan.neon", "phpunit_config": "web/core/phpunit.xml.dist", "composer_scripts": ["test", "lint"], "ci": "gitlab | github | none", "eslint": false, "stylelint": false },
  "frontend": { "package_manager": "npm | yarn | pnpm | none", "build": "vite | webpack | none", "sdc": true },
  "testing_frameworks": ["phpunit", "dtt", "behat", "cypress", "playwright", "nightwatch"],
  "mcp": { "configured_servers": ["drupal"], "fingerprint_hints": ["mcp-tools:serve"] },
  "agent_skills": { "project": [".agents/skills/drupal-configuration"], "vendor": [] },
  "deployment": { "hints": ["drush deploy in .gitlab-ci.yml"] }
}
```

Rules: every field is `null`/`unknown` when not determinable; the script never executes Drush or Composer (static only, safe in UNKNOWN environments). `project_kind` is decided by: `drupal/core` in `composer.lock` → site; `type: drupal-module` in `composer.json` + `*.info.yml` at root → module; `core/lib/Drupal.php` at root → core; `project` on drupal.org detected via `composer.json` `name: drupal/<x>` + `.gitlab-ci.yml` from the templates → contrib-module.

### 5.2 Version Router — MVP

Class is computed from `drupal.version` against `references/versions/matrix.md` (parsed by `drupal-facts`):

| Class | Rule | Behaviour |
|---|---|---|
| `current` | latest stable minor of the newest GA major | modern APIs; attributes; OOP hooks allowed if ≥ 11.1 |
| `previous` | any other minor still in security support | use only APIs present in that minor; `drupal-facts` answers "available in X.Y?" |
| `eol` | past EOL date | legacy mode: warn once per session, prefer minimal changes, do not modernize unrequested, mention upgrade path |
| `dev` | pre-release or `-dev` constraint | only if the lock file actually resolves to it; treat facts as unverified unless confirmed in installed source |

If the matrix is older than 120 days, the router says so in its note and the skill instructs a check against `drupal.org/project/drupal/releases` before relying on the class. The date is a warning, not a verdict (spec §64).

**Version-gated fact registry** (`references/versions/facts.yaml`): a short list, each entry `{id, statement, since, until, change_record, verify_in_core: <path or symbol>}`. Initial entries: OOP hooks 11.1, hook ordering/preprocess 11.2, theme OOP hooks 11.3, plugin attributes 10.2 / required 12.0, recipes 10.3, procedural hooks not deprecated, `RunTestsInSeparateProcesses` (pending verification), Drupal 12 PHP 8.5. `drupal-facts <id> <version>` prints `applies | not-applies | unknown` plus the citation. Skills quote the citation, not the memory.

### 5.3 Runtime Adapter (`scripts/drupal-runtime`) — MVP

Resolution order for each tool (`drush`, `composer`, `php`, `phpunit`, `npm`, `phpcs`, `phpstan`): project wrapper (composer script / Makefile / Taskfile target declaring it) → DDEV (`.ddev/config.yaml` and `ddev` on PATH and project running) → Lando (`.lando.yml`) → Docker Compose (`docker-compose*.yml` with a php-ish service) → native (`vendor/bin/<tool>`, then PATH) → none.

```json
{
  "schema": 1,
  "adapter": "ddev | lando | compose | wrapper | native | none",
  "state": "running | stopped | unknown",
  "commands": { "drush": "ddev drush", "composer": "ddev composer", "php": "ddev exec php", "phpunit": "ddev exec vendor/bin/phpunit", "npm": "ddev npm", "phpcs": "ddev exec vendor/bin/phpcs", "phpstan": null },
  "site_url": "https://myproj.ddev.site",
  "environment": { "class": "LOCAL", "evidence": ["ddev project", "hostname *.ddev.site"] },
  "browser": { "playwright": false, "chrome_mcp": true },
  "disposable_lab": { "possible": true, "engine": "ddev | docker | none" }
}
```

Environment classification (spec §53): `DISPOSABLE` only when the lab marker file the plugin itself wrote is present; `LOCAL` for DDEV/Lando/compose/`localhost`; `DEVELOPMENT`/`STAGING`/`PRODUCTION` from explicit signals (`settings.<env>.php`, `ENVIRONMENT`/`AH_SITE_ENVIRONMENT`/`PANTHEON_ENVIRONMENT`/`PLATFORM_BRANCH` env vars, non-local `site_url`); otherwise `UNKNOWN`. Anything not provably local is treated as UNKNOWN by the guard hook.

The adapter never starts, installs, or modifies anything. Starting a stopped DDEV project is a skill decision the user sees; installing Docker/DDEV is never done (spec §29).

### 5.4 Source-of-truth lookup (`scripts/drupal-lookup`) — MVP

`drupal-lookup <symbol|"topic words"> [--branch 11.x] [--kind function|class|hook|service|pattern]` prints ranked evidence:

1. **Installed core** (authority 1): `grep -rn` under the resolved docroot's `core/` for the symbol, `@deprecated` lines, and `*.api.php` documentation; for services, `core/core.services.yml` and module `*.services.yml`.
2. **Drupal Code Query** (authority 1b, optional): `/v1/symbol/search`, `/v1/symbol/{id}` lifecycle stamps, `/v1/search/code` for contrib usage examples.
3. **Change records** (authority 4): drupal.org `api-d7` filtered by branch, exact-title match first, then RSS scan.
4. **Docs**: canonical URL patterns for api.drupal.org and the coding-standards site (links only).

Backends 2–4 need network and are individually optional; each prints `unavailable: <reason>` rather than failing. Results carry the authority rank so the skill can say "core source says X; change record 3442349 confirms; blog post disagrees, ignored".

"Show me how core does it" (spec §13) is `drupal-lookup --kind pattern <pattern>` backed by `references/patterns/index.md`, a table mapping patterns (ConfigEntity, access check, lazy builder, kernel test for a route, event subscriber, queue worker, …) to core file paths per branch.

### 5.5 MCP capability detection — MVP (detection), P2 (advanced use)

Detection is a skill instruction, because only the model sees its tool list (D5). `drupal-runtime-verification/references/mcp-capabilities.md` holds the fingerprint table from the ecosystem analysis §5 and a capability map:

| Capability | MCP Tools | mcp_server + bridge | mcp 1.x | drush-mcp | Fallback |
|---|---|---|---|---|---|
| site.status | `get_site_status` | `tool_api__*system_status*` | `status` | `drupal_status` | `drush status --format=json` |
| modules.list | `get_site_status` | — | `status` | `drupal_drush pm:list` | `drush pm:list --format=json` |
| fields.describe | `get_content_type_fields` | `tool_api__*field*` | `jsonapi_schema` | `drupal_field_info` | `drush field:info` / config YAML |
| config.get / diff | `get_config`, `get_config_diff` | — | — | `drupal_config_get` | `drush config:get`, `config:status` |
| permissions.list | `get_permissions`, `get_roles` | — | — | `drupal_drush role:list` | `drush role:list` |
| logs.read | `analyze_watchdog` | — | — | `drupal_watchdog` | `drush watchdog:show` |
| cache.clear | `clear_all_caches` (write) | bridged | Drush tool | `drupal_cache_rebuild` | `drush cr` |
| routes.list | — | — | — | — | `drush route` / routing YAML |

Procedure: enumerate → fingerprint → map → probe once with the cheapest read tool → record scope. Tools classified shell-equivalent (`drupal_drush`, `drupal_php_eval`, `drupal_sql_query`, `ddev_exec`, whitelisted Drush) fall under the same destructive policy as Bash. Writes through MCP are refused when the server points at a non-local host.

`drupal-setup-mcp` (user-invocable) writes a project-scoped `.mcp.json` from a template after confirming the server module is installed and explaining the scope/uid choice. It never stores credentials; HTTP variants use `${VAR}` placeholders.

### 5.6 Verification levels and the evidence ledger — MVP

Every verification action appends a line to a session ledger the skills maintain in their own output (and, when Superpowers SDD is active, in its `progress.md`). Line format:

```
VERIFY L1 phpcs   PASS   "phpcs --standard=Drupal,DrupalPractice web/modules/custom/x" 0 errors
VERIFY L2 phpunit PASS   "ddev exec vendor/bin/phpunit -c web/core web/modules/custom/x/tests" 4 tests, 9 assertions
VERIFY L3 http    NOT VERIFIED  no runnable environment (adapter=none)
```

`drupal-verification` turns the ledger into the completion report (spec §59) using `references/gate-matrix.md`, which maps changed-file classes to required gates:

| Changed | L1 | L2 | Security | Access | Cacheability | L3 |
|---|---|---|---|---|---|---|
| `*.md`, docs | — | — | — | — | — | — |
| `*.yml` config/schema | lint, schema | `drush cim --preview`/kernel test | if permissions | if permissions | — | optional |
| PHP in `src/Controller|Form|Plugin|EventSubscriber` | phpcs, phpstan | kernel/functional | yes | yes if entity/user data | yes if render/response | if adapter |
| Twig / libraries | twig lint | functional-js optional | escaping | — | yes | browser if available |
| `.install` / `post_update` | phpcs | `drush updb` on runtime or kernel | — | — | — | if adapter |

A gate that is not applicable prints `NOT APPLICABLE`; one that could not run prints `NOT VERIFIED — <reason>`. The Stop hook (§8) only checks that a report exists when Drupal files changed.

### 5.7 Superpowers interop contract — MVP

Detection: the model sees `superpowers:*` skills in its skill list. No file probing, no `dependencies` entry.

| Superpowers moment | Drupal contribution | Mechanism |
|---|---|---|
| `brainstorming` "explore project context" | profile + version class | `drupal-project-understanding` description names "before proposing approaches for a Drupal change" |
| `brainstorming` "propose 2–3 approaches" | decision tables (service vs plugin, config vs content entity, …) and the design-review checklist | `drupal-architecture` description is design-time; body says it does not replace the approval gate |
| `writing-plans` Global Constraints | Drupal Global Constraints block (core/PHP floors, standards command, test command, config rule, DI rule, access/cache rules) | `drupal-workflow/references/global-constraints-template.md`; plan tasks name `drupal-superpowers:<skill>` where a subagent needs it |
| SDD implementer brief | exact commands from the runtime adapter | plan task template |
| SDD task reviewer `[GLOBAL_CONSTRAINTS]` | Drupal review lens | `drupal-code-review/references/review-lens.md` |
| `systematic-debugging` Phase 1–2 | Drupal failure modes and log sources | `drupal-debugging` description: "after reproducing, before changing code" |
| `test-driven-development` | test level choice, PHPUnit setup, integrity rules | `drupal-testing` |
| `verification-before-completion` | ledger and gate matrix | `drupal-verification` adds Drupal rows; does not restate their rule |
| `finishing-a-development-branch` test step | project test command | `drupal-runtime` output quoted in the plan |

Standalone: `drupal-workflow` supplies the compact process (§5.8) and says in its first line: "If `superpowers:brainstorming`, `superpowers:systematic-debugging`, or `superpowers:writing-plans` are available, use them for the process and use this skill only for the Drupal phases."

### 5.8 Complexity-sensitive orchestration — MVP

`drupal-workflow` classifies once, out loud, using observable signals rather than judgement calls:

| Class | Signals | Phases |
|---|---|---|
| **trivial** | single file, no PHP logic change, no config schema, no access/permission strings, no new route/service | Orient (cached profile) → Implement → L1 → two-line report |
| **bounded** | one module, existing patterns, no new entity type/integration/auth, ≤ ~3 files | Orient → Understand → Research (only unfamiliar API) → Test plan → Implement → L1 + L2 → Gate |
| **architectural** | new entity/config type, external integration, auth/permissions model, migration, upgrade, cross-module behaviour, or user says "design"/"architecture" | full §4 pipeline incl. design review, independent review, L3 when possible |

The class can be raised mid-task when evidence appears (e.g. a "label change" turns out to touch a permission), never silently lowered.

### 5.9 Disposable Drupal Lab — MVP (offer + DDEV/Docker), P2 (upgrade matrices)

Offered, never automatic (spec §29–30). Created under `${CLAUDE_PLUGIN_DATA}/labs/<name>` or a git worktree, marked with `.drupal-superpowers-lab` so the runtime adapter classifies it DISPOSABLE. Engines: DDEV when available, else Docker Compose from a plugin template (php + db), else none. The lab installs a minimal site (`drush site:install minimal`) and symlinks or copies the module under test. `drupal-runtime-verification/references/disposable-lab.md` documents creation, use, and the one-command teardown.

---

## 6. Skills

Common rules (from the writing-skills findings): frontmatter `name` + `description` (+ `user-invocable`, `paths`, `context` where noted); description third person, "Use when <moment> …", trigger conditions only, ≤ 300 chars, with grep-able Drupal tokens; body ≤ ~150 lines: Overview, When to use / not, Procedure, Decision rules, Interop block, Red flags, links to references. No workflow summary in descriptions. Every skill has trigger and no-trigger eval cases before it ships.

| Skill | Status | Trigger moment (description intent) | `paths` gate | User-invocable | Key references |
|---|---|---|---|---|---|
| `drupal-project-understanding` | MVP | first non-trivial task in a repo containing `composer.json` with `drupal/core`, or when facts about version/paths/runtime are needed; before proposing approaches | — | `/…:understand-project` | profile-fields |
| `drupal-workflow` | MVP | any Drupal change request when no Superpowers process skill is active; classifies complexity | — | no | global-constraints-template, plan-task-template |
| `drupal-research` | MVP | when unsure whether a Drupal API/hook/service/method exists or how core implements a pattern; before writing code that calls an unfamiliar API | — | no | source-hierarchy, core-patterns-index |
| `drupal-architecture` | MVP | designing how a feature should be built on Drupal: service vs plugin, hook vs event, config vs content entity, state vs config, queue vs sync, core vs contrib vs custom; during design, before a plan exists | — | no | decision-tables, design-review-checklist |
| `drupal-module-development` | MVP | creating or changing a custom module: `.info.yml`, `.services.yml`, `.routing.yml`, `.permissions.yml`, plugins, controllers, forms, entity types, update hooks | `**/modules/custom/**`, `*.info.yml` | no | services, plugins, routing, forms, entities, hooks, config-files |
| `drupal-testing` | MVP | choosing or writing PHPUnit Unit/Kernel/Functional/FunctionalJavascript tests, DTT/Behat/Playwright if present; before implementing a feature or bugfix; when tests fail | `**/tests/**`, `phpunit.xml*` | no | test-levels, phpunit-setup, test-integrity, project-frameworks |
| `drupal-debugging` | MVP | WSOD, "The website encountered an unexpected error", stale cache, container/plugin discovery errors, config import failure, failing tests; after reproducing, before changing code | — | `/…:debug` | drupal-failure-modes, log-sources |
| `drupal-security` | MVP | code touching access, permissions, routes, entity/user data, output rendering, Twig, file uploads, DB queries, redirects, AJAX; and explicit audits | `**/src/**`, `*.routing.yml`, `*.permissions.yml`, `*.twig` | `/…:audit` | checklist, access-patterns, output-escaping |
| `drupal-cacheability` | MVP | render arrays, responses, blocks, lazy builders, personalized or permission-dependent output, "shows stale/other user's data" symptoms | `**/src/**`, `*.twig` | no | metadata-rules, core-examples |
| `drupal-config` | MVP | config/install, config/schema, config sync, drift, overrides, config split, `drush cex/cim`, deciding config vs state vs content | `config/**`, `**/config/**`, `*.settings*.php` | no | storage-decision, schema, drift, deployment |
| `drupal-contrib-research` | MVP | before building non-trivial custom functionality, or when evaluating/adding a contrib module or Composer dependency | — | no | evaluation-criteria; `scripts/contrib-info` (drupal.org REST + Code Query) |
| `drupal-runtime-verification` | MVP | before running drush/composer/phpunit/npm, when verifying behaviour on a running site, when deciding whether L2/L3 evidence is obtainable, when MCP tools appear | — | no | levels, browser, disposable-lab, mcp-capabilities |
| `drupal-code-review` | MVP | reviewing a diff or change for correctness, API use, version compatibility, security, access, cacheability, tests, standards; explicit review requests | — | `/…:review` | review-lens |
| `drupal-verification` | MVP | about to claim a Drupal task is done, fixed, or passing | — | `/…:verify` | gate-matrix |
| `drupal-upgrade` | MVP | Drupal major/minor upgrades, deprecation removal, Rector/Upgrade Status output, PHP/Symfony compatibility, contrib major-version bumps | — | `/…:upgrade` | workflow, tooling, version-jumps |
| `drupal-setup-mcp` | MVP (small) | user asks to connect Claude to a Drupal site via MCP or to configure MCP Tools / mcp_server / drush-mcp | — | `/…:setup-mcp` | templates |
| `drupal-frontend` | P2 | Twig, libraries, behaviors/once, preprocess, theme suggestions, SDC, accessibility | `**/themes/**`, `*.twig`, `*.libraries.yml` | no | |
| `drupal-performance` | P2 | N+1 loads, slow queries, cache hit/miss, render pipeline profiling | — | no | |
| `drupal-migrate-api` | P2 | migration YAML, source/process/destination plugins, D7 content migration | `**/migrations/**` | no | |

Naming keeps the `drupal-` prefix from the brief (§7) even though the plugin namespace already disambiguates: the prefix survives when skills are copied into `.agents/skills/` or exported, and it matches the ai_best_practices convention users already see.

---

## 7. Agents

Only where isolated context, a different permission profile, or independent judgement pays (spec §8, D7).

| Agent | Status | Tools | Why an agent | Returns |
|---|---|---|---|---|
| `drupal-researcher` | MVP | Read, Grep, Glob, Bash (read-only commands), WebFetch, WebSearch | keeps large source/doc reads out of the main context; read-only by construction | ≤ 30-line evidence list with authority ranks and paths/URLs |
| `drupal-security-reviewer` | MVP | Read, Grep, Glob | independent of the implementer; must not edit | findings classified confirmed / probable / defense-in-depth / false positive with file:line |
| `drupal-code-reviewer` | MVP | Read, Grep, Glob, Bash (read-only) | "find reasons this could fail"; receives the diff as a file and the Drupal review lens | findings CRITICAL…INFORMATIONAL, verdict first |
| `drupal-test-engineer` | MVP | Read, Grep, Glob, Bash | isolates PHPUnit/PHPCS/PHPStan output (thousands of lines) | per-failure root-cause summary, exact failing assertion, suggested test level |
| `drupal-upgrade-specialist` | MVP | all | long inventory + Rector + rerun loops pollute the main context | upgrade plan, automated/manual change classification, remaining incompatibilities |
| `drupal-legacy-archaeologist` | P2 | Read, Grep, Glob, Bash (read-only) | mapping D7/8/9 code is long-running and read-only | architecture map, assumptions, business-critical behaviour, risks |
| `drupal-frontend-specialist` | P2 | all + browser tools | may become a `context: fork` skill instead | |
| `drupal-performance-reviewer` | P2 | Read, Grep, Bash | profiling output isolation | |
| `drupal-tech-lead` | dropped for MVP | | with Superpowers, its SDD controller is the tech lead; standalone, `drupal-workflow` + `drupal-architecture` cover it without a second orchestrator | |

Agent bodies preload the relevant skill via `skills:` frontmatter so a subagent that ignores skill discipline (Superpowers' `<SUBAGENT-STOP>`) still has the Drupal lens. Reviewers get `[GLOBAL_CONSTRAINTS]` and the diff path as input, never the implementer's narrative.

---

## 8. Hooks

`hooks/hooks.json`, all `command` type, all bash, all designed to finish in well under a second (spec §54, D8).

| Event | Matcher | Script | Behaviour |
|---|---|---|---|
| SessionStart | `startup\|clear\|compact` | `session-start` | Runs `drupal-profile` (cached). If no Drupal project: prints nothing. Otherwise emits the project brief (≤ 10 lines: version and router class, PHP, runtime adapter and environment class, custom module paths, test/lint commands) plus a 16-line **skill routing table** (moment → skill). Evidence for the table: the first with-plugin eval run fired only 10/16 trigger skills without it; the model did the right Drupal work but never invoked the skill. |
| PreToolUse | `Bash` | `guard-bash` | Parses the command. If it matches the destructive list (spec §52: `drush sql-drop|sql:drop|site:install|si|cim -y|config:import -y|entity:delete|sql:query <DROP|DELETE without WHERE>`, `DROP TABLE`, `rm -rf <project root>`, `git reset --hard`, `git clean -fd`, unbounded `composer update`) and environment class ≠ DISPOSABLE → exit 2 with a one-line reason and the safe alternative (`--preview`, `sql:dump` first, disposable lab). Also warns (exit 0 + `additionalContext`) on `drush cr` outside a runtime and on `\Drupal::` introduced by `sed`. |
| PostToolUse | `Edit\|Write` | `lint-php` | For `*.php|*.module|*.install|*.theme|*.inc|*.profile`: `php -l` via the resolved runtime; on error, `additionalContext` with the message. If `phpcs` resolves in < 1 s and a project or plugin ruleset exists, run it on that file only and report counts. Never phpstan, never phpunit. |
| Stop | — | `stop-gate` | If files under custom module/theme/config paths changed this session (Edit/Write tool calls or Bash edits in the transcript) and no `VERIFY` ledger line or completion report is present in the last assistant message → `decision: block` **once** with the reason "use drupal-verification and end with a report"; exits 0 when `stop_hook_active` is set, so it cannot loop. A context-only Stop hook cannot reach the model, hence the single soft block. |

Not included: SubagentStart injection (agents preload skills instead), UserPromptSubmit (nothing deterministic to add), FileChanged monitors (noisy; spec §36).

---

## 9. References and staleness

- Skill references are procedural and short, link to canonical sources, and never restate api.drupal.org.
- `references/versions/matrix.md` and `facts.yaml` carry frontmatter:

  ```yaml
  verified_against: { drupal: "11.4.x", drupal_dev: "12.0.0-alpha1" }
  last_reviewed: 2026-09-04
  sources: [drupal.org/about/core/policies/core-release-cycles/schedule, change records listed inline]
  ```
- `scripts/validate --staleness` fails CI when `last_reviewed` is older than 120 days, and `scripts/drupal-facts --check-upstream` (network, manual or scheduled) diffs the change-record feed for the router's branches against the fact registry and prints candidates, mirroring the monthly-issue pattern seen in the ecosystem.
- Facts re-derived from GPL sources are cited to the underlying change record or core file, never to the GPL skill (D11).

---

## 10. Evals and CI

### 10.1 Case format
Native Claude Code format so `claude plugin eval` works the day it is enabled: `evals/<group>/<case>/prompt.md` (frontmatter: `name`, `tags`, `runs`, `max_turns`, `timeout_seconds`, `plugins`, optional `scaffold_script`) + `graders/*.md` (`regex`, `tool_used`, `tool_order`, `file_exists`, `llm`). MCP mocks under `evals/mocks/<server>/`.

Groups map to spec §66–68: `trigger/` and `no-trigger/` (one pair per skill), `create-module/`, `security/`, `cache/`, `fake-api/`, `wrong-version/`, `debugging/`, `regression-test/`, `upgrade/`, `legacy/`, `runtime-none/`, `runtime-ddev/`, `mcp-present/`, `mcp-absent/`, `dangerous-env/`, `agents/` (spawn count, duplicate research).

### 10.2 Runner
`scripts/run-evals [--group g] [--case c] [--runs n]`: for each case, `claude -p "$(cat prompt.md)" --plugin-dir . --output-format stream-json --max-turns N` inside a temp copy of the referenced fixture; scores `regex`/`tool_used`/`tool_order`/`file_exists` deterministically; `llm` graders are scored by a second `claude -p` call with the criteria and the transcript, skipped with `--no-llm`. Output: JSON per case + a markdown summary; exit 1 below threshold. Trigger cases additionally flag premature tool use before the `Skill` call (Superpowers' technique).

### 10.3 Fixtures
Small synthetic trees under `fixtures/`, each with a real `composer.lock` slice (only `drupal/core` and needed packages) so `drupal-profile` works without `vendor/`: `site-current` (11.4), `site-previous` (10.6, EOL-adjacent), `site-legacy-d7`, `module-broken-service`, `module-xss-access`, `module-cache-leak`, `module-deprecated-d10`, `site-ddev` (with `.ddev/config.yaml`), `site-no-runtime`. Live integration evals (`evals/integration/`) build a Docker Compose Drupal from `fixtures/lab-compose/` and run only in CI or on request.

### 10.4 CI (`.github/workflows/ci.yml`)
1. `claude plugin validate --strict .`
2. `scripts/validate`: frontmatter lint (description starts with "Use when", ≤ 300 chars, no workflow verbs), internal link check, `hooks.json` schema, shellcheck, staleness.
3. Script unit tests (bats or plain bash asserts) for `drupal-profile`, `drupal-runtime`, `guard-bash` against fixtures.
4. `scripts/run-evals --group trigger --group no-trigger --no-llm` on every PR (cheap, deterministic).
5. Full eval groups nightly or on label, with a cost ceiling.

---

## 11. Security model (summary; full text in `docs/security.md`)

- **Environment classification** gates destructive operations deterministically (hook), with DISPOSABLE the only class where they run unprompted.
- **Least privilege by construction**: researcher and reviewers are read-only agents; MCP writes are refused for non-local hosts; MCP shell-equivalent tools inherit the Bash guard.
- **No secrets in the plugin**: templates use `${VAR}`; the profile script redacts anything under `settings*.php` and `.env`; reports never print credentials.
- **Repository is the source of truth** for code; MCP/Drush are for introspection and verification (spec §88).
- **Git is the user's**: no commit/push/reset by the plugin unless explicitly asked; the guard blocks `reset --hard` and `clean -fd`.
- **Review independence**: the security reviewer never implements; it classifies findings and must state what it could not verify.

---

## 12. Context budget

Always-on cost: manifest + 16 skill descriptions (176–308 chars) + 5 agent descriptions + hook config ≈ 1.8k tokens (measured 2026-09-04: 5575 description chars). SessionStart context (~25 lines incl. the routing table) only in Drupal repos, ≈ 400 tokens more. Per-skill body target ≤ 1.2k tokens; references loaded by explicit link. Verbose operations (test runs, log analysis, research, review, upgrade inventory) go to agents or `context: fork` skills and return ≤ 30 lines. `claude plugin details drupal-superpowers` is run in CI and the number recorded in `docs/evals.md` so regressions are visible.

---

## 13. Delivery phases

| Phase | Contents | Exit criterion |
|---|---|---|
| **MVP** (spec §81) | scripts (`drupal-profile`, `drupal-runtime`, `drupal-lookup`, `drupal-facts`, `run-evals`, `validate`), 16 MVP skills, 5 agents, 4 hooks, references (matrix, facts, patterns index), fixtures, trigger/no-trigger evals + the 14 scenario evals, README, docs/security, docs/runtime, docs/evals | acceptance scenarios 1, 2, 4 from spec §84–87 pass on the fixtures; CI green; `claude plugin validate --strict` clean |
| **P2** (spec §82) | frontend, performance, migrate-api skills; legacy-archaeologist, frontend, performance agents; disposable-lab upgrade matrices; advanced MCP use; core-contribution mode; architecture reports | scenarios 3 and 5 pass; real-repo tests on ≥ 3 projects across 10.6 / 11.4 / 12.x |
| **later** | multi-harness export (agentskills), upstream contributions to ai_best_practices, marketplace submission | |

---

## 14. Inputs for Stage 3 (taxonomy) and Stage 4 (evals)

Decisions to confirm or adjust in Stage 3, each with the default this document assumes:
1. `drupal-workflow` as a single fallback skill vs. folding the complexity classifier into `drupal-project-understanding` (default: separate, so the classifier has its own trigger/no-trigger evals).
2. `drupal-setup-mcp` as MVP or P2 (default: MVP, small, because MCP-present evals need a reproducible config).
3. `drupal-tech-lead` dropped (default: dropped; revisit if standalone evals show orchestration failures).
4. `paths:` gating on `drupal-security` and `drupal-cacheability` (default: on; verify it does not suppress triggering for symptom-based prompts like "shows the previous user's data").
5. JSON helper for scripts: first available of `php`, `python3`, `node`, with a grep fallback for the core version only (default: as stated).

Stage 4 should write the trigger/no-trigger pairs and the 14 scenario cases against the fixtures list in §10.3 before any skill body is written, so that every skill's first version is measured against a baseline.
