# Migration workflow

```
source analysis → destination model → field map → migration YAML (files/terms/users first, content last, translations after)
→ discovery (drush cr, migrate:status) → --limit run → messages → fix → rollback proof → full run → counts + spot checks → tests → report
```

## 1. Source analysis (read-only)
- CSV: `head -5`, `wc -l`, delimiter/enclosure, encoding (`file`), empties per column (`awk`), date formats, multi-value separators.
- D7 DB: `drush sql:query --database=migrate "SELECT type, COUNT(*) FROM node GROUP BY type"`, field tables `field_data_field_*`, `taxonomy_term_data`, `users`, `file_managed`; note `variable` table values that encode business rules.
- JSON/API: sample response, `item_selector`, pagination.
- Volume and relationships decide ordering and `migration_lookup` targets.

## 2. Field map (the spec)
| Source | Destination | Process | Notes |
|---|---|---|---|
| `name` | `title` | get | required |
| `tier` | `field_tier` (list_string) | skip_on_empty + static_map | 1 row empty |
| `website` | `field_website` (link) | trim + normalize scheme | 1 row without scheme |
| `tags` | `field_tags` (entity_reference: tags) | skip_on_empty, explode, trim, entity_lookup/migration_lookup | multi-value, spaces after commas |
| `created` | `created` | format_date Y-m-d H:i:s → U, timezone | |
| — | `uid` | constant 1 | or a users migration |

## 3–4. Ordering and dependencies
`migration_dependencies.required` lists migrations whose rows are looked up; files → media → terms → users → nodes → paragraphs (or paragraphs before nodes with `entity_reference_revisions` lookups) → menu links → redirects → translations.

## 5. Running (through the adapter, LOCAL/DISPOSABLE)
```bash
drush cr && drush migrate:status --tag=partners        # discovery (plain YAML needs cr; config entities need cim); --group needs migrate_tools
drush migrate:import partner_nodes --limit=10 --feedback=10
drush migrate:messages partner_nodes                    # per-row problems, with source ids
drush migrate:rollback partner_nodes                    # must work before the full run
drush migrate:import partner_nodes                      # full run
drush migrate:import partner_nodes --update             # re-import changed rows (track_changes / high-water)
drush migrate:reset-status partner_nodes                # only when stuck in Importing after a crash
```
Counts: `migrate:status` shows total/imported/unprocessed; compare with the source count; the id map is `migrate_map_partner_nodes` (`sourceid1`, `destid1`, `source_row_status`).

## 6. Tests
```php
final class PartnerNodesMigrationTest extends MigrateTestBase {   // Drupal\Tests\migrate\Kernel\MigrateTestBase
  protected static $modules = ['migrate', 'migrate_source_csv', 'node', 'taxonomy', 'field', 'text', 'link', 'options', 'user', 'system', 'partner_migrate'];
  public function testImport(): void {
    // install entity schemas + config, create the partner bundle/fields or installConfig(['partner_migrate']) …
    $this->executeMigration('partner_nodes');
    $this->assertCount(3, Node::loadMultiple());
    $this->assertSame('https://globex.example', Node::load(2)->get('field_website')->uri);
  }
}
```
Process plugins: `Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase`.

## 7. Report
- Field map (final), plugin choices with reasons
- Counts: source N, imported N, failed N (with `migrate:messages` summary), ignored N
- Rollback: proven / NOT VERIFIED
- Manual follow-ups (data fixes in source, editorial review list)
- Runtime: where it ran (LOCAL/DISPOSABLE), never against production data without approval and a DB dump

## Debugging
- "No migrations found": module not enabled, YAML not discovered (`drush cr`), `id` mismatch with file name.
- Stuck "Importing": `migrate:reset-status`.
- `entity_lookup` finds nothing: bundle/`value_key` wrong, whitespace; add `- plugin: callback callable: trim` first.
- Dates all 1970: wrong `from_format`; use `log` plugin to print raw values.
- Duplicates on re-run: unstable `ids` or missing `track_changes`.
