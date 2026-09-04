---
name: drupal-migrate-api
description: Use when writing or debugging Drupal migrations with the Migrate API: migration YAML, source, process, and destination plugins, migrate_plus/migrate_tools, drush migrate:import/status/rollback, id maps and high-water marks, or moving content from Drupal 7, CSV, JSON, or another database into Drupal; not for core version upgrades.
paths:
  - "**/migrations/**"
  - "**/migrate_plus.migration.*.yml"
  - "**/Plugin/migrate/**"
---

# Drupal Migrate API

**Core principle:** a migration is a pipeline over real source data: analyze the data first, map every field explicitly, run it idempotently with an id map, and prove it with counts and spot checks. Migrations are not the same thing as a Drupal version upgrade (`drupal-upgrade`); Drupal 7 → modern Drupal is a migration.

## When to use

Any migration YAML or plugin, `drush migrate:*`, D7/CSV/JSON/DB imports, stuck or partial migrations, rollback questions. Not for `composer update`/deprecation work.

## Procedure

1. **Source analysis first**: open the actual data (CSV head, D7 tables via `drush sql:query` read-only on the legacy DB, JSON sample); list columns, types, empties, encodings, date formats, delimiters, relationships (references by id vs name), volume. Write the field map table: source column → destination field → process → notes.
2. **Destination model**: content types, fields, vocabularies, users, files/media must exist (config export) before the migration; note bundle/field machine names from the site (`drush field:info`, config YAML), never from memory.
3. **Choose plugins** ([references/plugins.md](references/plugins.md)): source (`csv` from migrate_source_csv, `d7_node`/`d7_user`… from migrate_drupal, `url` from migrate_plus, `embedded_data`, custom `SqlBase`), process (`get`, `explode`, `skip_on_empty`, `callback`, `entity_lookup`/`entity_generate` from migrate_plus, `migration_lookup`, `format_date`, `sub_process`, `default_value`, `concat`, `static_map`, `file_copy`/`file_import`), destination (`entity:node`, `entity:user`, `entity:taxonomy_term`, `entity:media`, `entity:file`, `entity_reference_revisions` for paragraphs).
4. **Write the migration** ([references/workflow.md](references/workflow.md)): `migrations/<id>.yml` in a module (or `migrate_plus.migration.<id>.yml` config when the project uses migrate_plus config entities; follow the project), `migration_dependencies` for lookups, `migration_group` only with migrate_plus, `source.ids` and `track_changes`/`high_water_property` for incremental runs, `constants` for fixed values, files and media before the content that references them, translations as separate migrations with `translations: true`.
5. **Run and verify** through the adapter (`drupal-runtime-verification`): `drush migrate:status`, `migrate:import <id> --limit=10 --feedback=10` first, `migrate:messages <id>` for row errors, `migrate:rollback` to prove reversibility, `migrate:reset-status` only when stuck (`Idle`/`Importing`), then the full run with counts; spot-check entities (`drush php:eval` read-only) and the id map table `migrate_map_<id>`.
6. **Tests**: Kernel test with `MigrateTestBase`/`MigrateDrupalTestBase` + `embedded_data` or the real CSV fixture; assert counts and a few field values; process plugins unit-tested with `MigrateProcessTestCase`.
7. Report: field map, counts (source rows, imported, failed, ignored), messages, rollback proof, what remains manual.

## Decision rules

- D7 sites: `migrate_drupal` + `migrate_upgrade`/`migrate_drupal_ui` on Drupal ≤ 11.x (removed from core in 12.0; migrate on 10.6/11.x first, then upgrade), `drush migrate:upgrade --configure-only` to generate, then customize; verify per [references/d7.md](references/d7.md).
- Reference fields: `migration_lookup` to earlier migrations beats `entity_lookup` by name; `entity_generate` creates terms on the fly (say so, it hides data errors).
- Empty/invalid values: `skip_on_empty` (row or process), `skip_row_if_not_set`, `callback` with a validator; never let a bad row silently produce a broken entity.
- Idempotency: stable `ids`, `track_changes` for re-runs, no `uuid` collisions; never `migrate:import --update` on production without a backup and a rollback plan.
- Performance: `--limit`, `high_water_property`, avoid loading full entities in custom source plugins; large runs via cron/queue (`migrate_cron`/`migrate_scheduler` if present).
- Legacy business rules found during source analysis are documented in the map, not silently dropped (`drupal-legacy-archaeologist` for big D7 code bases).
- Migration ids, field machine names, process plugin ids, and comments are English even when the source data is not; source language stays in the data.

## Works with process skills

Architectural class in `drupal-workflow`; `superpowers:brainstorming` produces the field map as the spec; SDD tasks name this skill per migration.

## Red flags

| Thought | Reality |
|---|---|
| "The YAML looks right, run the full import" | Run `--limit=10`, read `migrate:messages`, then scale. |
| "explode on tags is enough" | It yields strings, not term IDs; add `entity_lookup`/`migration_lookup` and trim. |
| "Skip rollback support" | Rollback is how you fix a bad run without a DB restore. |
| "Dates will parse" | `format_date` needs the exact source format and timezone; empties need `skip_on_empty`. |
| "D7 upgrade = migration" | The D7 site is migrated into a fresh modern site; its code is not upgraded. |
