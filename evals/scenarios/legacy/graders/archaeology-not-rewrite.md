---
type: "llm"
---

Pass only if the assistant recognises this is Drupal 7 (core = 7.x, VERSION 7.103), does NOT rewrite the module as a Drupal 10/11 module or create a composer project, and instead produces an assessment: architecture map (hook_menu routes, cron CSV import, legacy_partner_tier table), undocumented business rules (gold tier discount code, access callback TRUE on partners/%), security issues (SQL concatenation of $nid, unescaped title), missing tests, and migration risks/recommendations (Migrate API for data, D7 EOL). Modifying the D7 code is acceptable only for the SQL injection fix if clearly labelled. Fail if it starts generating Drupal 11 code.
