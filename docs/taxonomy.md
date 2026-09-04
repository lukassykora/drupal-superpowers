# Skill and agent taxonomy

Stage 3 deliverable. Resolves the open defaults from `docs/architecture.md` §14 and fixes the **trigger surfaces**: the exact `name` and `description` of every MVP skill and agent. Descriptions are what Stage 4's trigger/no-trigger evals measure, so a description may change later only together with its eval results.

## 1. Decisions on the open defaults

| # | Question | Decision | Reason |
|---|---|---|---|
| 1 | `drupal-workflow` as a separate fallback skill? | **Yes, separate.** | The complexity classifier needs its own trigger/no-trigger cases; folding it into project-understanding would make that skill trigger on every prompt. |
| 2 | `drupal-setup-mcp` in MVP? | **Yes, last item of MVP.** | The MCP-present evals use a stub server from `fixtures/mcp-stub/`, so the skill is not needed for testing, but users need a safe way to get a project-scoped `.mcp.json`. |
| 3 | Drop `drupal-tech-lead`? | **Dropped.** | Superpowers' SDD controller or `drupal-workflow` + `drupal-architecture` cover orchestration. Revisit only if standalone evals show orchestration failures. |
| 4 | `paths:` gating on security/cacheability? | **No `paths` on symptom-driven skills** (security, cacheability, debugging, research, architecture). `paths` only on file-shaped skills: module-development, testing, config, frontend (P2), migrate-api (P2). | Prompts like "shows the previous user's data" mention no file; gating would suppress the right skill. Trigger evals include a no-file-mentioned case for each symptom skill. |
| 5 | JSON helper for scripts | `php` → `python3`, grep fallback for the core version only (no node branch implemented). | A Drupal host always has PHP; macOS/Linux CI has python3; node is common. Never `jq` (not guaranteed). |

Additional taxonomy decisions made here:
- **16 MVP skills, 5 MVP agents** (unchanged from architecture §6–7).
- **Skill names keep the `drupal-` prefix**; namespace form is `drupal-superpowers:drupal-security`. Accepted redundancy for portability.
- **User-invocable skills** (`user-invocable: true`, invoked as `/drupal-superpowers:<skill name>`): drupal-project-understanding, drupal-debugging, drupal-security, drupal-code-review, drupal-verification, drupal-upgrade, drupal-setup-mcp; there is no separate command layer and no short aliases.
- **One description budget**: ≤ 400 characters (validator limit; aim for ≤ 300), third person, starts with "Use when", trigger conditions and symptoms only, no workflow verbs (then, run, dispatch, review as a step), no "MUST".

## 2. MVP skills: names and descriptions (v1, to be measured)

| name | description |
|---|---|
| `drupal-project-understanding` | Use when starting non-trivial work in a Drupal codebase (composer.json with drupal/core, web/ or docroot/, *.info.yml) or when Drupal version, PHP version, docroot, custom module paths, config directory, test or lint commands, or local runtime (DDEV, Lando, Docker, none) are unknown or assumed. |
| `drupal-workflow` | Use when asked to build, change, or fix something in a Drupal project and no Superpowers process skill (brainstorming, systematic-debugging, writing-plans) is active; classifies the change as trivial, bounded, or architectural before any Drupal phases begin. |
| `drupal-research` | Use when unsure whether a Drupal API, hook, service, method, plugin type, or Twig function exists in this project's Drupal version, when a prompt names an API to use, or when asking how Drupal core implements a pattern; before writing code that calls an unfamiliar API. |
| `drupal-architecture` | Use when deciding how a feature should be built on Drupal: service vs plugin, hook vs event subscriber, config entity vs content entity, state vs config, queue vs synchronous, core vs contrib vs custom, new module vs existing; during design discussions, before an implementation plan exists. |
| `drupal-module-development` | Use when creating or editing a custom Drupal module: *.info.yml, *.services.yml, *.routing.yml, *.permissions.yml, *.links.*.yml, *.libraries.yml, config/install, config/schema, controllers, forms, plugins, entity types, hook implementations, update or post-update hooks. |
| `drupal-testing` | Use when choosing a Drupal test level or writing PHPUnit Unit, Kernel, Functional, or FunctionalJavascript tests, Nightwatch, DTT, Behat, Cypress, or Playwright tests in a Drupal project; before implementing a feature or bugfix, when a Drupal test fails, or when phpunit.xml or SIMPLETEST_* setup is unclear. |
| `drupal-debugging` | Use when a Drupal site or test shows an error or wrong behaviour: WSOD, "The website encountered an unexpected error", ServiceNotFoundException, plugin not found, route not found, stale cache, config import or schema errors, access denied, failing drush cr; after reproducing, before changing code. |
| `drupal-security` | Use when Drupal code handles access or permissions, routes, entity or user data, query parameters, output or Markup, Twig, file uploads, database queries, redirects, AJAX callbacks, or trusted callbacks, and when asked for a security audit or review of Drupal code. |
| `drupal-cacheability` | Use when Drupal code builds render arrays, responses, blocks, lazy builders, or output that depends on the current user, permissions, roles, language, query parameters, or entities, and when a page or block shows stale or another user's data. |
| `drupal-config` | Use when working with Drupal configuration: config/install, config/optional, config/schema, config sync directory, config split, overrides in settings.php, config drift, drush cex or cim, or deciding between config, state, tempstore, and content. |
| `drupal-contrib-research` | Use when considering a contrib module, theme, or Composer package for a Drupal need, before building non-trivial custom functionality, or when evaluating a module's Drupal version support, release status, maintenance, security coverage, and constraints. |
| `drupal-runtime-verification` | Use when about to run drush, composer, php, phpunit, phpcs, phpstan, or npm in a Drupal project, when verifying behaviour on a running site or in a browser, when Drupal MCP tools are present, or when deciding whether live verification is possible and how to state NOT VERIFIED. |
| `drupal-code-review` | Use when reviewing a Drupal diff, module, or pull request for correctness, Drupal API use, version compatibility, security, access, cacheability, configuration, tests, coding standards, and deployment impact, or when asked to review Drupal code. |
| `drupal-verification` | Use when about to say a Drupal task is done, fixed, working, or passing; produces the PASS / FAIL / NOT VERIFIED / NOT APPLICABLE completion report from coding standards, static analysis, tests, and runtime evidence. |
| `drupal-upgrade` | Use when upgrading Drupal core major or minor versions, removing deprecated Drupal APIs, handling Drupal Rector or Upgrade Status output, PHP or Symfony compatibility for Drupal, or bumping a contrib module's major version; not for content updates or routine composer update. |
| `drupal-setup-mcp` | Use when asked to connect Claude Code to a Drupal site through MCP, to configure MCP Tools, MCP Server, or drush-mcp, or to create a project .mcp.json for Drupal introspection. |

Deliberately excluded phrasing: "Drupal" alone is not a trigger; "any PHP" is not a trigger; `drupal-security` must not mention README, docs, or CSS; `drupal-upgrade` names its exclusions explicitly because "update" is ambiguous in Drupal (content, module, core).

## 3. MVP agents: names and descriptions

| name | description | tools |
|---|---|---|
| `drupal-researcher` (preloads drupal-research + drupal-contrib-research) | Read-only research on Drupal APIs, core implementations, change records, deprecations, and contrib options for a specific Drupal version. Use for questions that need reading many core files or web sources; returns ranked evidence with paths and URLs, never edits. | Read, Grep, Glob, Bash, WebFetch, WebSearch |
| `drupal-security-reviewer` | Independent read-only security review of a Drupal change or module: access, permissions, XSS, Twig escaping, SQL, CSRF, file handling, redirects, cache leaks. Use after implementation of code touching access or user data; classifies findings as confirmed, probable, defense-in-depth, or false positive. | Read, Grep, Glob |
| `drupal-code-reviewer` | Independent read-only review of a Drupal diff against the Drupal review lens: API correctness for the detected version, access, cacheability, config schema, tests, coding standards, deployment impact. Use for non-trivial changes; reports CRITICAL to INFORMATIONAL findings with file:line and looks for reasons the change could fail. | Read, Grep, Glob, Bash |
| `drupal-test-engineer` | Runs Drupal PHPUnit, PHPCS, and PHPStan through the resolved runtime and analyzes failures in isolation. Use when a test run is long or noisy; returns per-failure root cause, the failing assertion, and the cheapest test level that reproduces it. | Read, Grep, Glob, Bash |
| `drupal-upgrade-specialist` | Plans and executes Drupal major or minor upgrades and deprecation removal for a module or site: inventory, target constraints, Rector and Upgrade Status runs, manual changes, tests. Use for multi-file upgrade work that would flood the main context. | all |

Agents preload their matching skill (`skills:` frontmatter): researcher → drupal-research; security-reviewer → drupal-security + drupal-cacheability; code-reviewer → drupal-code-review; test-engineer → drupal-testing + drupal-runtime-verification; upgrade-specialist → drupal-upgrade.

Bash for read-only agents is restricted by instruction to read-only commands (`grep`, `find`, `git diff`, `composer show`, `drush ... --format=json` read commands); the PreToolUse guard applies inside agents too.

## 4. Hooks (unchanged from architecture §8)

`session-start`, `guard-bash`, `lint-php`, `stop-gate`. The guard's destructive list is the single source of truth for both hooks and the `dangerous-env` evals.

## 4b. Phase 2 additions (2026-09-04)

| name | description |
|---|---|
| `drupal-frontend` | Use when working in a Drupal theme or on front-end output: Twig templates, *.libraries.yml, Drupal.behaviors and once(), preprocess functions, theme suggestions, Single Directory Components, CSS/JS assets, or accessibility of rendered markup; and when reviewing theme code. |
| `drupal-performance` | Use when a Drupal page, route, Views listing, cron, queue, or migration is slow or memory-heavy, when code loads entities in loops (N+1), runs queries per row, calls external APIs in requests, or when asked to profile, benchmark, or review Drupal performance and cache hit rates. |
| `drupal-hard-problem` | Use when a Drupal architecture decision or debugging investigation has proven unusually hard: two or more falsified hypotheses, an intermittent or environment-dependent bug, a cross-module data-model or migration redesign, a cache/access interaction nobody can explain, or the user says the problem is hard; not for routine features, fixes, or first debugging passes. |
| `drupal-migrate-api` | Use when writing or debugging Drupal migrations with the Migrate API: migration YAML, source, process, and destination plugins, migrate_plus/migrate_tools, drush migrate:import/status/rollback, id maps and high-water marks, or moving content from Drupal 7, CSV, JSON, or another database into Drupal; not for core version upgrades. |

Agents: `drupal-legacy-archaeologist` (read-only inventory of D7/8/9 code, never rewrites), `drupal-frontend-specialist` (theme work with browser verification), `drupal-performance-reviewer` (read-only measurements and ranked hotspots). Contribution mode (contrib module / core) is a reference of `drupal-project-understanding`, not a skill.

## 4c. Model routing (2026-09-04)

Per the Claude Code model guidance (planning/architecture → Opus or Fable at `xhigh`; implementation → Sonnet; debugging/investigation → Fable or Opus; simple tasks → Sonnet/Haiku; subagents: cheap tiers for read-only work, Sonnet/Opus for review) and the maintainer's policy (Sonnet for everyday coding, Opus for harder coding, Fable for complex architecture/brainstorming and hard debugging):

| Component | model | effort | Why |
|---|---|---|---|
| everyday skills (workflow, project-understanding, research, module-development, testing, config, contrib-research, runtime-verification, verification, cacheability, frontend, migrate-api, setup-mcp) | inherit (session) | inherit | the user's daily model, typically `sonnet` or `opusplan` |
| `drupal-architecture` | `fable` | `xhigh` | architectural-class design and brainstorming for the rest of the turn; bounded work reads the decision tables without invoking it |
| `drupal-debugging`, `drupal-code-review`, `drupal-security`, `drupal-upgrade`, `drupal-performance` | `opus` | `high` | reasoning-heavy, mistakes are expensive |
| `drupal-hard-problem` (new) | `fable` | `xhigh` | debugging escalation after two falsified hypotheses, intermittent bugs, or a design `drupal-architecture` could not settle |
| agents `drupal-researcher`, `drupal-test-engineer` | `sonnet` | `medium` | read/grep and run/parse work |
| agent `drupal-frontend-specialist` | `sonnet` | `high` | implementation |
| agents `drupal-code-reviewer`, `drupal-security-reviewer`, `drupal-performance-reviewer`, `drupal-upgrade-specialist`, `drupal-legacy-archaeologist` | `opus` | `high` | independent judgement |

A skill's `model:` applies for the rest of the turn only; the session model resumes on the next prompt. Users override any agent by shipping a same-named agent in `.claude/agents/` or `~/.claude/agents/`, and can pin all subagents with `CLAUDE_CODE_SUBAGENT_MODEL(_FORCE)`. Values excluded by an organisation's `availableModels` fall back per Claude Code's rules.

## 5. What is intentionally absent

- No skill per Drupal subsystem (forms, entities, routing, Twig…). Those are `references/` pages under `drupal-module-development`, loaded when linked.
- No persona agents (backend dev, DevOps, content architect).
- No always-on rules file.
- No skill that duplicates a Superpowers process skill.
