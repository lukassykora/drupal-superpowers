---
name: drupal-research
description: Use when unsure whether a Drupal API, hook, service, method, plugin type, or Twig function exists in this project's Drupal version, when a prompt names an API to use, or when asking how Drupal core implements a pattern; before writing code that calls an unfamiliar API.
---

# Drupal research

**Core principle:** an API exists when you can point at its definition in the installed core of this version. Until then it is a hypothesis, including APIs the user named.

## When to use

- The task or the user names a method, service, hook, or attribute you have not seen in this project's core.
- You are about to write `->someMethod(` on a core object from memory.
- You need the canonical way core does something (access check, cache metadata, config entity, kernel test).
- A change record, deprecation, or "since which version" question comes up.

Not for: reading the project's own custom code (just read it), or well-known basics you can verify in one grep.

## Procedure

1. **Installed core first.**

   ```bash
   "${CLAUDE_PLUGIN_ROOT}/scripts/drupal-lookup" <symbol> --dir . [--kind function|class|hook|service|pattern]
   ```

   The script greps `<docroot>/core` for the definition and any `@deprecated` note, then queries Drupal Code Query and drupal.org change records if online, then prints the canonical doc URLs. Authority order is fixed: installed core > Drupal AI Best Practices > api.drupal.org for the branch > change records > official docs > coding standards > contrib docs/source > issues > community posts > general web ([references/source-hierarchy.md](references/source-hierarchy.md)).
2. **No definition in installed core** → the API does not exist in this version. Say so plainly, cite the search you ran, and offer the real alternative (or `NOT VERIFIED` if core is not present, e.g. no `vendor/`).
3. **Definition found** → read the signature and the docblock in place; check `@deprecated` and the version-gated facts registry:

   ```bash
   "${CLAUDE_PLUGIN_ROOT}/scripts/drupal-facts" check <fact-id> <drupal version>
   "${CLAUDE_PLUGIN_ROOT}/scripts/drupal-facts" list <drupal version>
   ```
4. **"How does core do it?"** → `drupal-lookup "<pattern>" --kind pattern` uses [references/core-patterns-index.md](references/core-patterns-index.md) (a copy of `references/patterns/index.md`) to name the core file to read, then read that file in the installed version.
5. Record what you verified in one line per API: `API: <symbol> — defined in <path> (<version>)` or `API: <symbol> — NOT FOUND in core <version>`.

## Decision rules

- Prefer a core implementation over a doc page, and a doc page over a blog post; never cite a blog post against the installed source.
- Version drift: a change record for 11.x does not apply to a 10.6 project; run `drupal-facts check` before quoting it.
- Contrib API: read the contrib module's source in `vendor/` or `modules/contrib/`, not its project page.
- Long reads (many core files, several change records) go to the `drupal-researcher` agent, which returns ≤ 30 lines of evidence.
- **One pass per task.** Collect the APIs first, verify them in one `drupal-lookup` round or one researcher dispatch, record the `API:` lines, and move on. Without installed core, do not substitute the web API by API: mark `NOT VERIFIED` and list them for the implementer; the web confirms a branch, never this checkout.

## Works with process skills

With `superpowers:brainstorming`, research feeds the "explore project context" and "approaches" steps. With `superpowers:systematic-debugging`, this is the "find working examples" move. Standalone, it is phase 3 of `drupal-workflow`.

## Red flags

| Thought | Reality |
|---|---|
| "The user said core provides it, so it must" | Users misremember too; the prompt is a hypothesis to verify. |
| "I remember this method from Drupal 9" | It may be renamed, deprecated, or removed; grep the installed core. |
| "api.drupal.org shows it" | For which branch? Check the URL's branch segment matches this project. |
| "No core here, I'll assume it exists" | Say NOT VERIFIED and ask, or reproduce in a disposable lab. |
