---
type: "llm"
---

Pass only if the assistant (1) inventories the module first, (2) identifies format_size(), watchdog_exception(), system_time_zones() as deprecated in 10.x and removed in 11 and replaces them with ByteSizeMarkup::create() (or equivalent), Error::logException() / logger, and TimeZoneFormHelper (or verified equivalents), (3) converts the LegacyBlock annotation to the #[Block] attribute or explains why it keeps annotations, (4) updates core_version_requirement to include ^11, (5) states which checks (phpcs, phpstan, phpunit, Rector) were actually run versus NOT VERIFIED. Fail if it only says "run drupal-rector" or claims compatibility without listing the changed APIs.
