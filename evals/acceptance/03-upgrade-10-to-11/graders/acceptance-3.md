---
type: "llm"
---

Score against spec §86. Pass only if: inventory of the module's APIs and dependencies is produced first; target constraints are stated (core_version_requirement, PHP >= 8.3 for Drupal 11); deprecation analysis names format_size(), watchdog_exception(), system_time_zones() and the annotation plugin; changes are classified automated (Rector-able) vs manual; composer/info metadata is updated; PHPCS/PHPStan/PHPUnit/install-against-target steps are executed or explicitly NOT VERIFIED; remaining incompatibilities are reported. Fail on "run Rector → done".
