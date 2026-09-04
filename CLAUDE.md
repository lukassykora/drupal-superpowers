# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repository is

**Drupal Superpowers** — an open-source Claude Code plugin: a Drupal-specific agentic engineering framework that works standalone or on top of the Superpowers plugin. It is *not* a prompt collection or a boilerplate generator. The full project brief lives in `docs/spec.md` (Czech, ~90 numbered sections plus addenda). Read the relevant sections of the brief before designing any component; do not paste it into skills or agents.

Current state (2026-09-04): MVP + Phase 2 implemented. 19 skills, 8 agents, 4 hooks, scripts, references, 53 evals, 9 fixtures, docs. `claude plugin validate --strict .` and `scripts/validate --staleness` pass. Stage 6–7 first pass done: trigger 16/16 (cumulative), no-trigger 16/16, scenarios 6/6, acceptance 1 and 4 PASS (1 after a contrib-research routing fix); details in `docs/evals.md` §8. Stage 8 done on two self-built real projects (Drupal 11.4 native + 10.6 Docker lab, `evals/integration/` in place via `DSP_LAB_D11`/`DSP_LAB_D10`): 7/7 pass; harness fixes recorded in `docs/evals.md`. Not yet tested on a user's own project with DDEV. Phase 2 (frontend, performance, migrate-api skills; legacy-archaeologist, frontend-specialist, performance-reviewer agents; contribution-mode reference; fixtures theme `acme`, `partner_directory`, `partner_migrate`) added 2026-09-04; measured: 6/6 trigger/no-trigger, 3/3 scenarios, acceptance 03 and 05 PASS → all five acceptance scenarios pass; results in `docs/evals.md` §8. Remaining from spec §82: disposable-lab upgrade matrices, advanced MCP use, architecture reports, CI recommendations. An ultracode audit on 2026-09-04 (141 findings against real core) was applied in full; its adversarial verification stage and the `english-code` LLM judge are pending because the account's monthly spend limit was hit (see `docs/evals.md` §8). `main.py` is a PyCharm placeholder, not part of the project.

## Commands

The plugin is validated and evaluated with the Claude Code CLI (tested with 2.1.260):

```bash
claude plugin validate --strict .          # structure, SKILL.md frontmatter, agents, hooks, MCP config (use in CI)
claude plugin validate --json .            # machine-readable report
claude plugin details drupal-superpowers   # component inventory + projected token cost (context budget check)
scripts/validate --staleness               # CI gate: plugin validate --strict + frontmatter/hooks/scripts/fixtures/evals/staleness
scripts/run-evals --group trigger --group no-trigger --no-llm --runs 1   # PR gate (38 cases, ~15 min)
scripts/run-evals --group scenarios --case cache                         # one scenario with LLM graders
scripts/run-evals --baseline ...                                         # no-plugin arm for comparison
scripts/drupal-profile fixtures/site-current --summary                  # exercise the detection scripts
scripts/drupal-facts check oop-hooks 10.6.16                            # version-gated fact registry
claude plugin eval .                       # native runner; early-access, NOT enabled on this account yet (prints "early access", exit 1)
claude plugin eval . --case 'skill-activation/*' --runs 1   # one eval group, single run
claude plugin eval . --tag security --threshold 0.8         # filter by tag, non-strict threshold
claude plugin eval . --scaffold --keep-temp                 # run cases that need fixture setup, keep temp dirs
claude plugin eval init --bare <case-name>                  # blank eval case template
claude --plugin-dir .                      # load this checkout as a plugin for a manual session
```

Because the native runner is gated, `scripts/run-evals` runs the same cases through `claude -p --plugin-dir . --output-format stream-json` and scores the deterministic graders; MCP is pinned per fixture via `fixtures/<site>/.mcp.json` + `--strict-mcp-config` and the stub server is `fixtures/mcp-stub/`. Integration evals that need a running Drupal use Docker in CI only; the end user's runtime is never assumed.

## Architecture

Layout follows the current Claude Code plugin spec (`claude plugin init --with skills,agents,hooks,mcp,lsp` shows the reference scaffold):

- `.claude-plugin/plugin.json` — manifest. `experimental.evals` may point the eval dir elsewhere than `evals/`.
- `skills/<capability>/SKILL.md` — 20 capability-based skills (project-understanding, hard-problem (Fable escalation), workflow, research, architecture, module-development, testing, debugging, security, cacheability, config, contrib-research, runtime-verification, code-review, verification, upgrade, setup-mcp, frontend, performance, migrate-api). Each SKILL.md holds only purpose, trigger conditions, workflow, decision rules and links; detail lives in `skills/<name>/references/*.md` and `skills/<name>/scripts/`. Keep SKILL.md well under 500 lines.
- `agents/*.md` — only agents that earn isolated context or a different permission profile: researcher (read-only), security-reviewer (read-only), code-reviewer (read-only), performance-reviewer (read-only), legacy-archaeologist (read-only), test-engineer, upgrade-specialist, frontend-specialist. No tech-lead (dropped, taxonomy §1).
- `hooks/hooks.json` — deterministic guardrails (destructive command detection), targeted lint after PHP edits, completion-gate support. Never full PHPUnit/PHPStan on every edit.
- `references/` — versioned knowledge packs with staleness frontmatter (`verified_against`, `last_reviewed`, `sources`).
- `evals/`, `fixtures/` — 53 eval cases (trigger/no-trigger per skill, 14 scenarios, agents, acceptance) and 8 synthetic fixtures with planted defects; format and grading rules in `docs/evals.md`. Fixtures have no `vendor/` or core, so L2/L3 evidence is impossible there by design.
- `docs/` — `spec.md` (the brief), `ecosystem-analysis.md` (Stage 1 result: platform facts, Superpowers interop contract, surveyed projects, MCP landscape, decisions D1–D11), `architecture.md` (Stage 2: layout, JSON contracts for `drupal-profile`/`drupal-runtime`, hooks, eval/CI shape), `taxonomy.md` (Stage 3: the authoritative skill and agent descriptions), `evals.md` (Stage 4: case format, runner design, planted defects), then security and runtime.

Cross-cutting mechanisms that span several files and must stay consistent:

- **Version Router** (spec §10–11): one skill set, not per-version agents. Version comes from `composer.lock` / `composer.json` / runtime, classified as current / previous supported / EOL / dev. Version-specific behaviour (e.g. `#[Hook]` attributes only from 11.1, plugin attributes vs annotations) is decided by router output plus the installed core source, never by memorised facts.
- **Runtime Adapter** (spec §27–29, §92): resolves how to run `drush`, `composer`, `php`, `phpunit`, `npm` (DDEV, Lando, docker compose, native, project wrapper, none) and classifies the environment (DISPOSABLE / LOCAL / DEVELOPMENT / STAGING / UNKNOWN / PRODUCTION). Every skill that executes anything goes through it; nothing hard-codes `drush cr`.
- **Source-of-truth hierarchy** (spec §12–13): installed core source > Drupal AI Best Practices > api.drupal.org > change records > docs > standards > contrib docs/source > issues > web. "Show me how core does it" is the default research move.
- **Completion gate** (spec §31, §59–61): three verification levels (static / Drupal automated / live). Reports use PASS, FAIL, NOT VERIFIED — reason, NOT APPLICABLE. Gates are relevance-aware; unverified never becomes "should work".
- **Superpowers interop** (spec §4): when Superpowers is present, reuse its brainstorming / debugging / TDD / planning / review workflows and add Drupal intelligence; when absent, lightweight fallbacks. No second orchestration system. The installed Superpowers source for reference is under `~/.claude/plugins/cache/claude-plugins-official/superpowers/<version>/`.

## Rules for working in this repo

- Follow the implementation order in spec §91. Stages 1–5 are done (ecosystem analysis, architecture, taxonomy, evals, MVP). Stage 6–7: run `scripts/run-evals`, compare with the baseline results under `evals/results/*-baseline`, and fix triggering/orchestration/hallucination problems by editing skill descriptions and bodies together with re-run results. Stage 8: real Drupal repositories. Stage 9–10: docs polish and validation. Do not start by mass-generating skills.
- Licensing: MIT. Never copy text from GPL sources (ai_best_practices, drupaltools/skills, ablerz, any drupal.org project) or unlicensed repos; re-derive facts and cite core or change records. Adapted MIT content (Superpowers, grasmash) goes in `ATTRIBUTION.md`.
- Before adding any skill, agent, hook or reference, answer: does this raise Claude's ability to solve a Drupal task correctly? If not, don't add it. Prefer reuse (Superpowers, Drupal AI Best Practices) over duplication; check licences before copying.
- Skill descriptions are trigger surfaces; the authoritative text is `docs/taxonomy.md` §2 and each has a pair under `evals/trigger` and `evals/no-trigger`. Change a description only together with re-run results. Write a skill body only after running its trigger case and scenario with `--baseline` and recording the observed failures.
- Reference packs must not be static "Drupal N does X" lists; they must point at how to verify against the installed version.
- Drush is the only supported Drupal CLI (site-local `vendor/bin/drush`; `drush generate` for scaffolding). Drupal Console is EOL and must never be recommended or used (spec §93).
- Model routing: everyday skills inherit the session model; `drupal-architecture` and `drupal-hard-problem` set `model: fable` for the turn (architectural-class design, brainstorming, hard debugging); debugging/review/security/upgrade/performance set `model: opus`; agents carry `model:`/`effort:` per `docs/taxonomy.md` §4c. Keep new skills on inherit unless reasoning dominates.
- Tooling policy for the plugin itself: keep hooks fast, keep SKILL.md files short, check `claude plugin details` token cost when adding context-loaded content.
