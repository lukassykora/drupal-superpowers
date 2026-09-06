---
name: drupal-workflow
description: Use when asked to build, change, fix, or plan something in a Drupal project (including "write a plan", "do not implement yet", hand-off documents) and no Superpowers process skill (brainstorming, systematic-debugging, writing-plans) is active; classifies the change as trivial, bounded, or architectural before any Drupal phases begin.
---

# Drupal workflow (standalone fallback)

If `superpowers:brainstorming`, `superpowers:systematic-debugging`, or `superpowers:writing-plans` is active, use those for the process and use this skill only for the Drupal phase table, the Global Constraints template, and the Drupal plan rules. Do not run two orchestrations.

**Core principle:** the amount of process matches the size of the change, decided once, out loud, from observable signals.

## Step 1: classify, out loud, before any tool call

Write one line: `Class: trivial | bounded | architectural — signal: <what in the request decides it>`. Do this before reading files; refine it after Orient if evidence changes it (raise only, never lower silently).

| Class | Observable signals | Phases |
|---|---|---|
| **trivial** | one file; no PHP logic change; no config schema, permission, route, or service touched | Orient (cached profile) → Implement → static check → `drupal-verification` (two-line report) |
| **bounded** | one module; existing patterns; ≤ ~3 files; no new entity type, integration, or auth model | Orient → Understand → Research (only unfamiliar API) → decision tables read directly (no `drupal-architecture`) → Plan only when the user asks for one → Test plan → Implement → L1 + L2 → `drupal-verification` |
| **architectural** | new entity/config type, external integration, permission model, migration, upgrade, cross-module behaviour, or the user says design/architecture | full pipeline below including a written plan, independent review, L3 when a runtime exists |

A request for a plan, a hand-off document, or "do not implement yet" is a **plan-only** task: run the phases up to Plan and stop there with the plan's handoff.

## Step 2: phases (architectural; subsets for smaller classes)

This skill orchestrates; the domain knowledge lives in the skills named below. **Invoke them with the Skill tool at the phase where they are named**; do not substitute your own version of their content.

1. **Orient** — invoke `drupal-project-understanding`: version + class, runtime + environment class, paths, commands.
2. **Understand** — read the code on the execution path (route → controller/form → services → entities → templates) before editing anything. Name the files you read.
3. **Research** — invoke `drupal-research` for any API you cannot point to in the installed core (including APIs the user named). Research is one pass per task: one `drupal-lookup` round in the installed core, or one `drupal-researcher` dispatch for a list of APIs; the main agent does not repeat the same look-ups on the web afterwards, and an API that stays unverified is marked `NOT VERIFIED` while the work continues. For every new feature, endpoint, or module (bounded or architectural), invoke `drupal-contrib-research` first and write one line per candidate: core module, contrib module, or custom, with the reason; only then design custom code.
4. **Design** — architectural class: invoke `drupal-architecture` (this escalates the turn to the strongest model); write 2–3 options with trade-offs; pick the smallest Drupal-native one. Bounded class: do not invoke it; read `drupal-architecture/references/decision-tables.md` and follow the module's existing pattern. Then run the applicable rows of `drupal-architecture/references/design-review-checklist.md` (security, access, cacheability, config, deployment) as part of the design; do not invoke the domain skills yet.
5. **Test plan** — invoke `drupal-testing`: cheapest layer that proves the behaviour; for bugs, the regression test comes first and must fail.
6. **Plan** — architectural class, or whenever the user asked for a plan: follow [references/writing-plans.md](references/writing-plans.md). Read every file the plan will touch and locate every API it calls in the installed core before writing; without installed core, write the document first with `NOT VERIFIED` marks and run one verification pass over its API table afterwards. The document goes to `docs/plans/` (never under `web/`), carries the filled Global Constraints, and each task contains the test code, the implementation code, and the resolved commands; nothing points at "§4" or "Task 1". Plan-only task: hand over here.
7. **Implement** — invoke `drupal-module-development` for module files; the smallest correct change; project conventions over preferences; no unrelated refactoring. With a plan, one task at a time in its order. Invoke a domain skill only for the file class in front of you, at most once per task: `drupal-security` when writing a route requirement, access check, or output of user data; `drupal-cacheability` when writing a render array or response that varies per user, permission, language, or query; `drupal-config` when writing `config/install` or `config/schema`. A change that touches none of those loads none of them.
8. **Verify** — invoke `drupal-runtime-verification` before running any command: L1 static, L2 automated, L3 live, each recorded as a `VERIFY` line.
9. **Review** — for architectural or security/access/cache-touching changes, dispatch `drupal-code-reviewer` and/or `drupal-security-reviewer` with the diff as a file.
10. **Gate** — invoke `drupal-verification` to build the report (two lines for trivial), ending with the git handoff (nothing staged or committed; the user gets the commands). Only then say done.

## Plans

When writing a plan (yours or `superpowers:writing-plans`), paste [references/global-constraints-template.md](references/global-constraints-template.md) filled from the profile and shape every task with [references/plan-task-template.md](references/plan-task-template.md). Subagents see only their brief, so every Drupal rule they must follow lives in these constraints and in the task itself. The Drupal-specific plan rules (read first, version gate, access and cache metadata in the code blocks, no commit step) are in [references/writing-plans.md](references/writing-plans.md); with Superpowers they complement `writing-plans`, standalone they are the Plan phase.

## Red flags

| Thought | Reality |
|---|---|
| "Simple ticket, I'll skip the profile" | The version decides which API exists; 30 seconds of profile beats a wrong API. |
| "I'll fix it and then add a test" | For bugs, test first (RED) or the test proves nothing. |
| "This needs a service, a plugin manager, and an event" | Bounded work uses the existing pattern in the module. |
| "Let me also clean up this old code" | Out of scope; report it separately. |
| "I'll commit so the work isn't lost" | The user owns git; print the commit command instead. |
| "The plan can describe the tests; the implementer writes them" | A plan step without the code is a placeholder; the implementer sees only that task. |
| "IMPLEMENTATION_PLAN.md next to the module is handy" | Anything under `web/` ships with the code; plans live in `docs/plans/`. |
| "The user writes Czech, so I'll name the module in Czech" | Code, machine names, comments, and reports are English; only the conversation follows the user's language. |
