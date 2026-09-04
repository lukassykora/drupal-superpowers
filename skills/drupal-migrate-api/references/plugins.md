# Migrate plugins (core + common contrib)

Verify availability in the installed code: core plugins live under `core/modules/migrate/src/Plugin/migrate/{source,process,destination}` and `core/modules/migrate_drupal`; contrib under `modules/contrib/migrate_plus/src/Plugin/migrate/`. Attributes `#[MigrateSource]`, `#[MigrateProcess]`, `#[MigrateDestination]` on 10.3+ (annotations earlier).

## Source
| Plugin | From | Use |
|---|---|---|
| `embedded_data` | core | tests, tiny fixed datasets (`data_rows`, `ids`) |
| `csv` | migrate_source_csv | `path`, `ids`, `header_row_count`, `delimiter`, `enclosure`, `fields` (when no header) |
| `url` | migrate_plus | JSON/XML/SOAP over HTTP or files: `data_fetcher_plugin: file|http`, `data_parser_plugin: json|xml|simple_xml`, `item_selector`, `fields`, `ids` |
| `d7_node`, `d7_user`, `d7_taxonomy_term`, `d7_file`, `d7_field_values`… | migrate_drupal | D7 database via the `migrate` DB key (`$databases['migrate']['default']` in settings) |
| `table` | migrate_plus | any table on any DB key |
| custom `SqlBase` / `SourcePluginBase` | your module | when nothing fits; implement `query()`/`initializeIterator()`, `fields()`, `getIds()`, `prepareRow()` |

## Process (chain them as a list under one destination field)
| Plugin | Purpose |
|---|---|
| `get` (implicit) | copy `source` value; `source: [a, b]` gives an array |
| `default_value` | `default_value: 1`, `strict: true` |
| `static_map` | `map: {gold: premium}`, `default_value`, `bypass: true` |
| `callback` | `callable: trim`, `callable: [Class, method]`, `unpack_source: true` |
| `explode` / `concat` / `flatten` | delimiter handling; combine with `callback: trim` via `sub_process` or `- plugin: callback` on each |
| `skip_on_empty` | `method: row` (skip the whole row) or `process` (skip this field) |
| `skip_row_if_not_set` | key must exist |
| `format_date` | `from_format`, `to_format`, `from_timezone`, `to_timezone`, `settings: {validate_format: false}` |
| `migration_lookup` | `migration: partner_terms`, `source: tag_id`, `no_stub: true` (or stubs for forward refs) |
| `entity_lookup` (migrate_plus) | find by `value_key`/`bundle`; `entity_generate` creates when missing (document it) |
| `sub_process` | iterate multi-value arrays: `process: { target_id: { plugin: migration_lookup, ... } }` |
| `file_copy` / `file_import` (migrate_file) | files → `entity:file`; media via a second migration |
| `machine_name`, `substr`, `str_replace`, `urlencode`, `dedupe_entity`, `log`, `null_coalesce` | utilities; `log` prints values during debugging |

## Destination
| Plugin | Notes |
|---|---|
| `entity:node` (+ `default_bundle`), `entity:taxonomy_term`, `entity:user`, `entity:file`, `entity:media`, `entity:paragraph` (`entity_reference_revisions` from ERR) | `translations: true` for translation migrations; `overwrite_properties` for updates |
| `entity:node_type`, `entity:field_storage_config` … | structure migrations (D7 upgrades) |
| `config` | simple config values |
| `table` (migrate_plus) | custom tables |
| `null` | test/dry runs |

## Migration YAML skeleton
```yaml
id: partner_nodes
label: Partner nodes from CSV
migration_tags: [partners]
source:
  plugin: csv
  path: modules/custom/partner_migrate/data/partners.csv
  ids: [id]
  header_row_count: 1
  constants:
    uid: 1
process:
  title: name
  field_tier:
    - plugin: skip_on_empty
      method: process
      source: tier
    - plugin: static_map
      map: { gold: gold, silver: silver, bronze: bronze }
      default_value: null
  field_website:
    - plugin: callback
      callable: trim
      source: website
    - plugin: callback
      callable: [\Drupal\partner_migrate\Normalize, url]   # adds https:// when missing
  field_tags:
    - plugin: skip_on_empty
      method: process
      source: tags
    - plugin: explode
      delimiter: ','
    - plugin: callback
      callable: trim
    - plugin: entity_lookup            # or migration_lookup to a term migration
      entity_type: taxonomy_term
      bundle: tags
      value_key: name
  created:
    plugin: format_date
    from_format: 'Y-m-d H:i:s'
    to_format: 'U'
    from_timezone: 'Europe/Prague'
    source: created
  uid: constants/uid
destination:
  plugin: entity:node
  default_bundle: partner
migration_dependencies:
  required: []
```
Structure: `migration_group:` and `migrate_plus.migration.*.yml` config entities only with migrate_plus; plain `migrations/*.yml` in a module needs `migrate` only (and a cache rebuild to discover changes: `drush cr`).
