---
type: "llm"
---

Pass only if the transcript reads data/partners.csv and derives the fixes from it: field_tags exploded strings must be trimmed and mapped to taxonomy term IDs (entity_lookup / migration_lookup / entity_generate with the caveat), the empty tier for row 3 handled (skip_on_empty or default), the website without scheme for row 2 normalized (callback) or validated, and the missing migrate_plus dependency for migration_group noted or migration_group removed; and the report says the import was NOT VERIFIED (no runtime) or was run with --limit and messages shown. Fail if it rewrites the migration from memory without reading the CSV or claims a successful import that did not run.
