---
verified_against: { drupal: "11.4.x", drupal_dev: "12.0.0-alpha1" }
last_reviewed: 2026-09-04
sources: [drupal.org release notes 10.6.0 / 11.4.0 / 12.0.0-alpha1, core release cycle schedule]
---

# Version jumps

Check `references/versions/matrix.md` for current classes; confirm release notes when this file is older than 120 days.

| Jump | Prerequisite | PHP | Database | Symfony | Drush | Notable removals / changes |
|---|---|---|---|---|---|---|
| 9.x → 10.x | 9.4+ recommended; deprecations fixed on 9.5 | 8.1+ | MySQL 5.7.8 / MariaDB 10.3.7 / PgSQL 12 / SQLite 3.26 | 6.x | 11/12 | CKEditor 4 → 5, Classy/Stable/Seven/Bartik removed (contrib), jQuery UI removed, Quick Edit/RDF/Aggregator/HAL/Tracker removed (contrib) |
| 10.x → 11.x | **10.3+** (11.0 requires 10.3); fix deprecations on 10.6 | **8.3+** | MySQL 8.0 / MariaDB 10.6 / PgSQL 16 / SQLite 3.45 | 7.x | 13 | Book, Forum, Statistics, Tour, Actions UI, Tracker → contrib; `format_size`, `watchdog_exception`, `system_time_zones` removed; annotation-to-attribute for plugin types deprecated in 11.2; `#[Hook]` from 11.1 |
| 11.x minors | previous minor | 11.3+: 8.3–8.5 | | 7.x | 13 (14 for 11.3+) | 11.4 deprecates Ban, Contact, Field Layout, History, Migrate Drupal (+UI), Stable 9 (Telephone is not deprecated in 11.4.6; it is removed in 12.0 per the alpha notes); Standard profile no longer ships Article/Page |
| 11.x → 12.x | **11.4+** | **8.5 only** | MySQL 8.0 / MariaDB 10.11 / PgSQL 18 or 19 (sources differ) / SQLite 3.45 | 8.1 | 14 | removed to contrib: Ban, Contact, Field Layout, History, Settings Tray, Shortcut, Telephone, Migrate Drupal (+UI), Stable 9; proposed: Search, Toolbar, Claro, Olivero; annotation support for plugin types removed in 13 |
| 7.x → 10/11 | none (migration, not upgrade) | 8.3+ | | | | Migrate Drupal removed in 12.0: migrate to 10.6/11.x first; use `migrate_drupal` + `migrate_upgrade` there |

Always: DB dump before `updb`; `drush updb` before `cim`; check `core_version_requirement` of every module; re-roll patches; re-run Functional tests on the target.
