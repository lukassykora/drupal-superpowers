# Decision tables

Each table lists the observable condition that selects a row. When two rows match, prefer the one with fewer moving parts. Core examples to read are in the pattern index (`skills/drupal-research/references/core-patterns-index.md`, also `references/patterns/index.md` at the plugin root).

## Service vs plugin vs event subscriber vs hook

| Condition | Choose | Because |
|---|---|---|
| One implementation, used by several callers, stateless | service (`*.services.yml`, constructor DI, `autowire: true` on 10.3+ if the project uses it) | cheapest, testable in Unit/Kernel |
| Several interchangeable implementations, discovered by ID, configurable per instance | plugin type (manager + attribute class; annotation fallback only for contrib types that still need it) | discovery, derivatives, per-instance config |
| React to something that happened (request, entity save via `hook_entity_*` vs event, config save, migration) | event subscriber for Symfony/Drupal events; hook when core exposes only a hook (most entity and form alterations) | use what core dispatches |
| Alter a build/form/route/query owned by another module | the matching `hook_*_alter` (procedural or `#[Hook]` on ≥ 11.1 where the project already uses OOP hooks) | alters are hooks by design |
| Cross-cutting request behaviour (redirects, headers, access on many routes) | event subscriber on `KernelEvents::REQUEST`/`RESPONSE`, or access check service on routes | |

## Data storage

| Condition | Choose | Notes |
|---|---|---|
| Site-builder-defined structure or definitions that must deploy between environments (types, presets, mappings) | config entity | schema in `config/schema`; exported by `drush cex` |
| A few site-wide settings | simple config (`config/install/<module>.settings.yml` + schema, `ConfigFormBase`) | override per environment in settings.php `$config[...]` |
| User-generated, revisionable, translatable, access-controlled, listable data | content entity (fields, `EntityAccessControlHandler`, `views_data`) | gets Views/JSON:API/Migrate for free |
| Runtime values that must not deploy (last cron run, external tokens' expiry, flags) | State API | never in config |
| Per-user or per-session drafts, multistep form data | PrivateTempStore / SharedTempStore | expires |
| Derived or cached data that can be rebuilt | Cache API bin or key/value with expiry | never the source of truth |
| Secrets | environment / secret manager via settings.php | never config, state, or code |
| Large per-entity structured data without querying needs | field on the entity (map/serialized) | still access-controlled |

## Processing model

| Condition | Choose |
|---|---|
| User needs the result in this request | synchronous service call |
| Work may fail and must retry, or many items | Queue API + QueueWorker plugin (cron) or a dedicated queue runner |
| Admin-triggered long job with progress | Batch API |
| Periodic maintenance | `hook_cron` (or queue + cron) |
| External API call in a request | synchronous with timeout + cache; otherwise queue |

## Module placement

| Condition | Choose |
|---|---|
| Extends a concept an existing custom module owns | that module |
| New, reusable responsibility; could be enabled independently | new module under the project's custom path |
| Site-specific glue (theme-level tweaks, one-off alters) | the project's existing "site" glue module if one exists |
| Reusable across sites/projects | contrib-shaped module (composer.json, `core_version_requirement`, tests, README) |

## Core vs contrib vs custom

| Question | Evidence to gather |
|---|---|
| Does core already do it? | core module list for this version, `drupal-lookup --kind pattern` |
| Contrib candidate? | `drupal-contrib-research`: supported versions, release status, security coverage, maintainer activity, usage, open compatibility issues, dependencies, constraints |
| Custom? | only when the above fail or the need is truly site-specific; then the smallest surface, with tests |

## Hook style by version

| Project | Style |
|---|---|
| ≤ 11.0 | procedural hooks in `.module` |
| ≥ 11.1, module has no `src/Hook/` yet | either; follow neighbours; do not convert existing hooks unrequested |
| ≥ 11.1, module already uses `src/Hook/` | `#[Hook]` classes; `#[LegacyHook]` shims only if 10.x support is required |
| Install/update/theme/requirements hooks | procedural in every version |
