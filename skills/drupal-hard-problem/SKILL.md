---
name: drupal-hard-problem
description: Use when a Drupal debugging investigation has proven unusually hard: two or more falsified hypotheses, an intermittent or environment-dependent bug, a cache/access/revision interaction nobody can explain, or an architecture decision drupal-architecture could not settle, or the user says the problem is hard; not for routine features, fixes, or first debugging passes.
model: fable
effort: xhigh
---

# Drupal hard problem (escalation)

**Core principle:** most Drupal work runs on the everyday model. This skill exists so that the genuinely hard cases get the deepest reasoning available for the rest of the turn, without paying for it on every task. Invoking it switches this turn to the `fable` alias (Claude Code's most capable tier) at `xhigh` effort; the session model returns on the next prompt.

## When to use

- Debugging: `drupal-debugging` has run, at least two hypotheses were falsified with evidence, and the cause is still unknown; or the symptom is intermittent, load-dependent, or differs between environments; or it involves the render cache, access grants, and revisions interacting.
- Architecture: `drupal-architecture` produced options but they all fail a hard constraint (multi-site, multilingual + workspaces, migration of a legacy data model, integration with an external system of record, performance at scale), or the decision is expensive to reverse.
- The user explicitly says the problem is hard or asks for maximum effort.

When not to use: first debugging pass, features with an obvious Drupal pattern, anything the workflow classified trivial or bounded.

## Procedure

1. Restate the problem in one paragraph with the evidence collected so far (what was tried, what was falsified, what remains unexplained). Do not restart from zero.
2. Continue the same Drupal discipline: installed core is the source of truth (`drupal-research`), every claim gets a `VERIFY` line (`drupal-runtime-verification`), security and cacheability are checked (`drupal-security`, `drupal-cacheability`).
3. For debugging: widen the hypothesis space deliberately (cache layers, request context, database driver differences, hook ordering, event subscriber priorities, service overrides, container parameters, PHP version behaviour) and design one experiment per hypothesis before running any.
4. For architecture: write the decision as options with explicit constraints and failure modes, name what would change the recommendation, and state the migration/rollback path.
5. End with the usual completion report (`drupal-verification`) and say explicitly which parts remain unexplained or `NOT VERIFIED`.

## Red flags

| Thought | Reality |
|---|---|
| "Let me escalate right away, it looks tricky" | Two falsified hypotheses first; most "tricky" bugs are a wrong service ID. |
| "Now that I have the big model I'll rewrite the module" | Scope stays the same; the model changed, not the task. |
| "I'll skip the VERIFY lines, this reasoning is solid" | Evidence rules do not depend on the model. |
