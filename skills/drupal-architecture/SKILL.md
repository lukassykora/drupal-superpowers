---
name: drupal-architecture
description: Use when deciding how a feature should be built on Drupal: service vs plugin, hook vs event subscriber, config entity vs content entity, state vs config, queue vs synchronous, core vs contrib vs custom, new module vs existing; during design discussions, before an implementation plan exists.
---

# Drupal architecture

**Core principle:** the smallest design that uses the Drupal mechanism built for the job. Core or contrib before custom; the existing pattern in this project before a new one; no abstraction for a need nobody has yet.

## When to use

- Designing a feature that adds a route, entity, storage, integration, or workflow.
- Choosing between two Drupal mechanisms (the tables below).
- Reviewing someone else's design for Drupal fit.

Not for: three-line bugfixes, label changes, or work inside an existing pattern (just follow the pattern).

## Procedure

1. **Facts first.** Version class, existing modules, project conventions (from `drupal-project-understanding`). Read the module that is closest to what you are adding and note its patterns (DI style, plugin vs service, config vs state, test level).
2. **Does it exist already?** Core module or feature, then contrib (`drupal-contrib-research`), then existing custom code. Write one line per candidate with the reason to accept or reject.
3. **Pick mechanisms with the decision tables** in [references/decision-tables.md](references/decision-tables.md). Say which row applies and why.
4. **Options.** For architectural work write 2–3 options (one paragraph each: mechanism, files touched, what it costs, what it cannot do). Recommend one; name what would change the recommendation.
5. **Design review rows.** Run only the applicable rows of [references/design-review-checklist.md](references/design-review-checklist.md): security, access, cacheability, configuration, translations, revisions/moderation, multilingual, deployment, backward compatibility, testability, performance.
6. **Output** a short design: mechanism, module placement, data model, access model, cache model, config/state decisions, test level, deployment notes. Nothing more.

## Decision rules (summary; full tables in the reference)

- **Service** for stateless logic used by others; **plugin** when there will be several interchangeable implementations discovered by a manager; **event subscriber** to react to a Symfony/Drupal event; **hook** when core defines that hook and the version's hook style (procedural, or `#[Hook]` from 11.1) fits the project.
- **Config entity** for deployable, site-builder-managed definitions; **content entity** for user-generated, revisionable, translatable data; **simple config** for a handful of settings; **state** for runtime values that must not deploy; **tempstore** for per-user/session drafts; **key/value** for internal caches of data.
- **Queue/cron/batch** when work is long, retryable, or triggered by many events; synchronous when the user must see the result now.
- **New module** when the responsibility is new and reusable; otherwise extend the module that owns the concept.
- **Contrib** only when maintained for this Drupal version, covered by the security policy or acceptably small, and its constraints fit; otherwise custom with the smallest surface.

## Works with process skills

If `superpowers:brainstorming` is active, use this skill while exploring approaches and presenting the design; it does not replace the approval gate. If `superpowers:writing-plans` is active, put the chosen mechanisms and the design-review outcomes into the plan's Global Constraints. Standalone, this is phase 4 of `drupal-workflow`.

## Red flags

| Thought | Reality |
|---|---|
| "A custom table with a service is simpler than an entity" | You lose access, revisions, translations, Views, JSON:API, tests helpers; an entity is usually less code. |
| "I'll store it in config so it's editable" | Config deploys; per-environment or user-generated values must not. |
| "Let me build a small plugin framework" | One implementation is a service; a manager needs ≥ 2 real implementations. |
| "Contrib module X is popular" | Popular is a signal; supported version, security coverage, and constraints decide. |
| "Access can be added later" | Access is part of the data model; design it with the entity or route. |
