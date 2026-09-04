# Drupal Superpowers

A Claude Code plugin that makes Claude work through Drupal tasks the way an experienced Drupal team does: establish the facts about the project, apply the APIs of *this* Drupal version, choose the smallest Drupal-native design, design access and cacheability in, implement to Drupal standards, and prove the result with static, automated, and live evidence before calling it done.

It is not a prompt collection and not a code generator. It is an orchestration and verification layer on top of the sources Drupal already has: the installed core source, change records, api.drupal.org, the coding standards, and the community's AI Best Practices.

Status: **0.2.0-dev, MVP + Phase 2 (frontend, performance, Migrate API, legacy/frontend/performance agents)** (see [docs/architecture.md](docs/architecture.md) §13 and the evals results in [docs/evals.md](docs/evals.md) §8).

## What it does

| Capability | How |
|---|---|
| Never assumes the Drupal version, docroot, runtime, or commands | `scripts/drupal-profile` and `scripts/drupal-runtime` read `composer.lock`, `.ddev/`, `.lando.yml`, settings, CI files; the SessionStart hook shows a 10-line brief in Drupal repos |
| Applies APIs that exist in the project's version | Version router (current / previous / EOL / dev), a small registry of version-gated facts with change-record citations, and `scripts/drupal-lookup` that greps the installed core before anything else |
| Designs Drupal-natively | Decision tables: service vs plugin vs hook vs event, config vs content vs state vs tempstore, queue vs sync, core vs contrib vs custom; a design-review checklist for security, access, cacheability, config, translations, deployment |
| Treats security and cacheability as correctness | Checklists, access layers, output-escaping rules, cache metadata rules, and read-only reviewer agents that classify findings (confirmed / probable / defense-in-depth / false positive) |
| Uses tests as evidence | Cheapest proving layer (Unit → Kernel → Functional → FunctionalJavascript), regression test first for bugs, integrity rules (no weakened assertions) |
| Verifies at three levels and says which ran | L1 static, L2 Drupal automated, L3 live; every claim is `PASS`, `FAIL`, `NOT VERIFIED — reason`, or `NOT APPLICABLE` |
| Runs safely | A PreToolUse guard blocks `drush sql:drop`, `site:install`, `cim -y`, `entity:delete`, destructive SQL, `git reset --hard`, unbounded `composer update` outside disposable environments |
| Works with or without Superpowers | With Superpowers installed, its process skills run first and Drupal skills supply the domain knowledge inside them; standalone, `drupal-workflow` provides a compact process |
| Uses Drupal MCP when present, never depends on it | Fingerprint-based detection of MCP Tools, MCP Server 2.x, drush-mcp and others; read-only first; shell-equivalent tools get the Bash guard's rules |

## Installation

Requires Claude Code 2.1.x.

```bash
# from a local checkout (development)
claude --plugin-dir /path/to/drupal-superpowers

# or add the dev marketplace and install
claude plugin marketplace add /path/to/drupal-superpowers
claude plugin install drupal-superpowers@drupal-superpowers-dev
```

Nothing else is installed on your machine. The plugin's scripts are bash and use `php` or `python3` for JSON parsing when available.

## Quick start

Open Claude Code in a Drupal project. On session start you get a brief like:

```
Drupal project detected (drupal-superpowers).
Drupal 11.4.6 (current, composer.lock) · PHP >=8.3 · kind site · docroot web
custom modules: saved_items contact_note
config: ../config/sync · ci: gitlab · tests: phpunit · lint: phpcs.xml.dist + phpstan.neon.dist
runtime: ddev (running) · env: LOCAL
drush: ddev drush · phpunit: ddev exec vendor/bin/phpunit · phpcs: ddev exec vendor/bin/phpcs
```

Then ask for work in plain language. Skills activate on their own:

- "Add an authenticated endpoint returning the current user's saved items." → project understanding, architecture, module development, security, cacheability, testing, runtime verification, completion gate.
- "This page sometimes shows data for the previous user after login. Fix it." → debugging, cacheability, testing, verification.
- "Upgrade legacy_tools to Drupal 11." → upgrade skill and the upgrade-specialist agent.

Explicit entry points: `/drupal-superpowers:understand-project`, `:debug`, `:audit`, `:review`, `:verify`, `:upgrade`, `:setup-mcp`.

## Architecture in one paragraph

Nineteen capability skills (short `SKILL.md`, detail in `references/`), eight agents (read-only researcher, security reviewer, code reviewer, performance reviewer, legacy archaeologist; test engineer, upgrade specialist, frontend specialist), four hooks (session brief, Bash guard, PHP lint, stop-gate reminder), and a handful of zero-dependency scripts (`drupal-profile`, `drupal-runtime`, `drupal-facts`, `drupal-lookup`). Version knowledge is computed from the project and verified against the installed core; the only static data is a dated support matrix and a small facts registry with citations. Full design: [docs/architecture.md](docs/architecture.md); why it looks like this: [docs/ecosystem-analysis.md](docs/ecosystem-analysis.md).

## Superpowers interoperability

Superpowers (obra/superpowers) is optional. When present, its `brainstorming`, `systematic-debugging`, `writing-plans`, `test-driven-development`, and `verification-before-completion` run first (their own priority rule); Drupal skills are consulted inside those steps and feed a Drupal "Global Constraints" block into plans so that Superpowers' implementer and reviewer subagents follow Drupal rules. Nothing here duplicates a Superpowers process skill or injects a second bootstrap.

## Runtime support

| Runtime | Detection | Commands |
|---|---|---|
| DDEV | `.ddev/config.yaml` + `ddev` binary | `ddev drush`, `ddev exec vendor/bin/phpunit`, … |
| Lando | `.lando.yml` + `lando` | `lando drush`, … |
| Docker Compose | `docker-compose*.yml` / `compose.yaml` with a PHP service + `docker` | `docker compose exec <svc> …` |
| Native | `vendor/` present | `vendor/bin/drush`, `vendor/bin/phpunit` |
| None | nothing runnable | L1 only; runtime claims are `NOT VERIFIED`; a disposable DDEV/Docker lab is offered, never created automatically |

Project wrappers (`composer test`, Makefile targets) take precedence. Details: [docs/runtime.md](docs/runtime.md).

### Optional DDEV / disposable lab

If your project has no runnable environment, Claude may offer to create a throw-away Drupal environment under the plugin's data directory (DDEV when installed, otherwise Docker Compose). It never installs Docker or DDEV and never touches your project's own environment. See `skills/drupal-runtime-verification/references/disposable-lab.md`.

### Optional Drupal MCP

`/drupal-superpowers:setup-mcp` writes a project-scoped `.mcp.json` from templates for MCP Tools (recommended, read scope), MCP Server 2.x, or drush-mcp. Credentials go in environment variables, never in the file. Detection at runtime is by tool names, so any of these servers configured by hand works too.

## Security model

Summary in [docs/security.md](docs/security.md): environment classification (DISPOSABLE / LOCAL / DEVELOPMENT / STAGING / UNKNOWN / PRODUCTION), the destructive-command guard, read-only agents, MCP least privilege, no secrets in the plugin or transcripts, no git operations unless asked.

## Development

```bash
scripts/validate --staleness          # plugin validate --strict + frontmatter, hooks, scripts, fixtures, evals, staleness
scripts/run-evals --dry-run           # list cases
scripts/run-evals --group trigger --group no-trigger --no-llm --runs 1   # PR gate
scripts/run-evals --group scenarios --case cache                        # one scenario with LLM graders
scripts/run-evals --baseline ...      # the no-plugin arm
```

Evals: [docs/evals.md](docs/evals.md). Contributing: [CONTRIBUTING.md](CONTRIBUTING.md). Attribution for adapted ideas: [ATTRIBUTION.md](ATTRIBUTION.md).

## Troubleshooting

- **No session brief in a Drupal repo**: run `"$CLAUDE_PLUGIN_ROOT/scripts/drupal-profile" . --summary` manually; the hook prints nothing when no `composer.lock` with `drupal/core` (or D7 `includes/bootstrap.inc`) is found above the cwd.
- **Guard blocked a command you approved**: the guard cannot see conversation approvals. Run it yourself with `! <command>` in the prompt, or mark a disposable environment with a `.drupal-superpowers-lab` file at its root.
- **Wrong runtime chosen**: check `drupal-runtime . ` JSON `declared` vs `adapter`; a declared DDEV without the `ddev` binary falls through to the next option.
- **Version class says `unknown`**: the branch is missing from `references/versions/matrix.md`; check drupal.org releases and update the matrix (with `last_reviewed`).
- **Skills do not trigger**: descriptions are in `docs/taxonomy.md`; run the trigger evals; if Superpowers is installed its process skills fire first by design.

## License

MIT. See [LICENSE](LICENSE).
