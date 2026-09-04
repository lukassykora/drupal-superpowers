---
name: drupal-workflow
description: Use when asked to build, change, or fix something in a Drupal project and no Superpowers process skill (brainstorming, systematic-debugging, writing-plans) is active; classifies the change as trivial, bounded, or architectural before any Drupal phases begin.
---

# Drupal workflow (standalone fallback)

If `superpowers:brainstorming`, `superpowers:systematic-debugging`, or `superpowers:writing-plans` is available, use those for the process and use this skill only for the Drupal phase table and the Global Constraints template. Do not run two orchestrations.

**Core principle:** the amount of process matches the size of the change, decided once, out loud, from observable signals.

## Step 1: classify, out loud, before any tool call

Write one line: `Class: trivial | bounded | architectural — signal: <what in the request decides it>`. Do this before reading files; refine it after Orient if evidence changes it (raise only, never lower silently).

| Class | Observable signals | Phases |
|---|---|---|
| **trivial** | one file; no PHP logic change; no config schema, permission, route, or service touched | Orient (cached profile) → Implement → static check → `drupal-verification` (two-line report) |
| **bounded** | one module; existing patterns; ≤ ~3 files; no new entity type, integration, or auth model | Orient → Understand → Research (only unfamiliar API) → Test plan → Implement → L1 + L2 → `drupal-verification` |
| **architectural** | new entity/config type, external integration, permission model, migration, upgrade, cross-module behaviour, or the user says design/architecture | full pipeline below, independent review, L3 when a runtime exists |

## Step 2: phases (architectural; subsets for smaller classes)

This skill orchestrates; the domain knowledge lives in the skills named below. **Invoke them with the Skill tool at the phase where they are named**; do not substitute your own version of their content.

1. **Orient** — invoke `drupal-project-understanding`: version + class, runtime + environment class, paths, commands.
2. **Understand** — read the code on the execution path (route → controller/form → services → entities → templates) before editing anything. Name the files you read.
3. **Research** — invoke `drupal-research` for any API you cannot point to in the installed core (including APIs the user named). For every new feature, endpoint, or module (bounded or architectural), invoke `drupal-contrib-research` first and write one line per candidate: core module, contrib module, or custom, with the reason; only then design custom code.
4. **Design** — invoke `drupal-architecture` for mechanism choices; write 2–3 options with trade-offs for architectural work; pick the smallest Drupal-native one. Then, when the change touches access, permissions, routes, user data, output, queries, uploads, or redirects, invoke `drupal-security`; when it renders anything that varies by user, permissions, language, or query, invoke `drupal-cacheability`; when it adds or changes config, invoke `drupal-config`.
5. **Test plan** — invoke `drupal-testing`: cheapest layer that proves the behaviour; for bugs, the regression test comes first and must fail.
6. **Implement** — invoke `drupal-module-development` for module files; the smallest correct change; project conventions over preferences; no unrelated refactoring.
7. **Verify** — invoke `drupal-runtime-verification` before running any command: L1 static, L2 automated, L3 live, each recorded as a `VERIFY` line.
8. **Review** — for architectural or security/access/cache-touching changes, dispatch `drupal-code-reviewer` and/or `drupal-security-reviewer` with the diff as a file.
9. **Gate** — invoke `drupal-verification` to build the report (two lines for trivial); only then say done.

## Global Constraints template

When writing a plan (yours or `superpowers:writing-plans`), paste [references/global-constraints-template.md](references/global-constraints-template.md) filled from the profile. Subagents see only their brief, so every Drupal rule they must follow lives in these constraints and in the task template ([references/plan-task-template.md](references/plan-task-template.md)).

## Red flags

| Thought | Reality |
|---|---|
| "Simple ticket, I'll skip the profile" | The version decides which API exists; 30 seconds of profile beats a wrong API. |
| "I'll fix it and then add a test" | For bugs, test first (RED) or the test proves nothing. |
| "This needs a service, a plugin manager, and an event" | Bounded work uses the existing pattern in the module. |
| "Let me also clean up this old code" | Out of scope; report it separately. |
