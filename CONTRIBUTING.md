# Contributing

## Ground rules

1. **Evidence before prose.** A skill or description changes only together with eval results: run the relevant `evals/trigger` and `evals/no-trigger` cases (and the scenario it affects) before and after, and paste the summary table into the PR.
2. **No knowledge silos.** Reference material links to canonical sources (installed core paths, change records, coding standards, drupal.org docs) instead of restating them. Version-gated facts go into `references/versions/facts.json` with a change-record URL and a `verify_in_core` path.
3. **Licensing.** MIT. Never paste text from GPL projects (ai_best_practices, drupaltools/skills, ablerz, any drupal.org project) or from unlicensed repositories. Adapted ideas from MIT/Apache sources are listed in `ATTRIBUTION.md`. Re-derive facts and cite the primary Drupal source.
4. **Context budget.** `SKILL.md` ≤ ~150 lines / ~1200 words (`scripts/validate` warns above); heavy tables in `references/`; verbose work in agents.
5. **No git operations by the plugin.** Scripts and skills never commit, push, or reset.

## Adding a skill

1. Confirm it is a capability, not a sub-topic: it answers "what can Claude now do" and has a distinct trigger moment (see `docs/taxonomy.md`). Sub-topics become `references/` pages of an existing skill.
2. Write the trigger and no-trigger cases first (`evals/trigger/<name>/`, `evals/no-trigger/<name>/`) and, if there is a planted-defect scenario, the fixture change in `fixtures/` with its README line.
3. Run the baseline: `scripts/run-evals --baseline --case <name>` and `--case <scenario>`; record verbatim what the model does wrong without the skill.
4. Write the description (third person, "Use when …", trigger conditions and symptoms only, ≤ 300 chars, no workflow verbs, grep-able Drupal tokens) into `docs/taxonomy.md` and the `SKILL.md` frontmatter.
5. Write the body: Overview (core principle), When to use / not, Procedure, Decision rules, Works with process skills (Superpowers interop block), Red flags table from the observed baseline failures, links to references.
6. Run `scripts/validate` and the trigger/no-trigger evals with the plugin; iterate on the description until both pass across 2+ runs.
7. Add the skill to `docs/taxonomy.md` and, if user-invocable, to the README entry points.

## Adding a reference pack or version fact

- Reference files carry frontmatter `verified_against`, `last_reviewed`, `sources` when their content is version-dependent.
- `references/versions/matrix.md` holds the human table and the `<!-- json:support -->` block that scripts read; edit both.
- A fact in `facts.json` needs `id`, `statement`, `since`, `until` (null when still valid), `change_record`, `verify_in_core`; mark anything not confirmed as `UNVERIFIED` in `change_record` and say so in `note`.
- `scripts/validate --staleness` fails when `last_reviewed` is older than 120 days; refresh by re-checking the sources, not by bumping the date.

## Adding an agent

Only when isolated context, a different permission profile, or independent judgement is needed. Frontmatter: `name`, `description` (when to delegate), `tools` (read-only where possible), `skills` to preload. Add an `evals/agents/` case that checks it is not spawned for trivial work.

## Adding a hook

Hooks must finish well under a second, never block on non-destructive actions, and must have a deterministic test in `scripts/validate` or a synthetic-stdin check. Destructive-command patterns go into `hooks/scripts/guard-bash` and `evals/scenarios/dangerous-env`.

## Running the checks

```bash
scripts/validate --staleness
scripts/run-evals --group trigger --group no-trigger --no-llm --runs 1
scripts/run-evals --group scenarios --runs 2            # nightly-class; uses LLM graders
```

## Pull request checklist

- [ ] `scripts/validate --staleness` clean
- [ ] Trigger/no-trigger evals pass for touched skills (before/after table in the PR)
- [ ] Scenario evals touched by the change pass or the failure is explained
- [ ] No GPL/unlicensed text; ATTRIBUTION.md updated if ideas were adapted
- [ ] Docs updated (`docs/taxonomy.md` for descriptions, `docs/evals.md` §8 for results)
