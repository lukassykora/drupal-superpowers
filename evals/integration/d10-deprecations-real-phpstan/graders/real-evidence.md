---
type: "llm"
---

Pass only if the three removed-in-11 APIs (format_size, watchdog_exception, system_time_zones) and the annotation-based Block are identified with evidence from this real 10.6 core (the @deprecated docblocks in web/core, phpstan with deprecation rules, or Upgrade Status), replaced by APIs that exist in 10.6 (ByteSizeMarkup, Error::logException / injected logger, TimeZoneFormHelper, #[Block] attribute or a stated reason to keep the annotation), and the module was re-enabled or cache-rebuilt with drush inside the Docker runtime (docker compose exec ... drush) with the output shown. Fail if any replacement API is asserted without being checked in web/core and if no drush run appears.
