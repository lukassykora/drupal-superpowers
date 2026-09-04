# Upgrade report template

Fill these three tables in this order and print them in the final reply. The first one is written **before** any edit; the second **before** implementing; the third at the end. Omitting one is an incomplete upgrade, not a style choice.

## 1. Inventory (before any change)
```
Inventory: <module> on Drupal <current> (class <router class>), PHP <constraint>, Drush <version>
| item | where | used by |
|---|---|---|
| hook_form_system_site_information_settings_alter() | legacy_tools.module:12 | procedural hook |
| format_size() | legacy_tools.module:24 | legacy_tools_upload_limit() |
| watchdog_exception() | legacy_tools.module:31 | legacy_tools_log() |
| system_time_zones() | legacy_tools.module:16 | form alter |
| @Block annotation | src/Plugin/Block/LegacyBlock.php:11 | plugin discovery |
| dependencies / composer | legacy_tools.info.yml (core_version_requirement: ^10), none in composer.json | — |
Target: Drupal <target minor>, PHP <floor>, Drush <major>; removed modules affecting this module: <none|list>
```

## 2. Classification (before implementing)
```
| change | automated (Rector rule / mechanical) | manual | why |
|---|---|---|---|
| format_size() → ByteSizeMarkup::create() | yes (drupal-rector FormatSizeToByteSizeMarkupRector) | — | 1:1 replacement, CR 2999981 |
| watchdog_exception() → Error::logException() | yes (rector rule) | review logger injection | signature differs, CR 2932520 |
| system_time_zones() → TimeZoneFormHelper::getOptionsList() | partial | yes | return shape/grouping differs, CR 3023528 |
| @Block annotation → #[Block] attribute | yes (rector attribute rule) | — | core plugin type, CR 3395582 |
| core_version_requirement ^10 → ^10.3 || ^11 | — | yes | policy decision, tests on both |
Rector: <ran with palantirnet/drupal-rector x.y> | NOT RUN — not installed (classification from the facts registry and change records)
```

## 3. Final report
```
Changed APIs: <old → new (CR link)> …
Composer/info changes: …
VERIFY L1 phpcs …
VERIFY L1 phpstan (deprecation rules, phpVersion <target>) …
VERIFY L2 phpunit (current) … | (target lab) … | NOT VERIFIED — reason
VERIFY L2 install/updb/cr on target: …
Remaining incompatibilities: <item — owner — proposed handling> | none
Deployment order: … ; rollback: DB dump before updb
```
