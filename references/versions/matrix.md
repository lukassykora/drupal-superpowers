---
verified_against:
  drupal: "11.4.x"
  drupal_dev: "12.0.0-alpha1"
last_reviewed: 2026-09-04
sources:
  - https://www.drupal.org/about/core/policies/core-release-cycles/schedule
  - https://www.drupal.org/project/drupal/releases
  - https://www.drupal.org/docs/getting-started/system-requirements/php-requirements
  - https://www.drupal.org/project/drush (Packagist drush/drush)
---

# Drupal support matrix

Human-readable view of the data block below. `scripts/drupal-facts class <version>` and
`scripts/drupal-profile` read the JSON block, so edit both together. When `last_reviewed` is older than
120 days, `scripts/validate --staleness` fails and the router adds a "verify against drupal.org" note.

| Branch | Class today | Security support ends | PHP | Drush |
|---|---|---|---|---|
| 7.x | eol (2025-01-05) | ended | 5.6–8.1 | 8 |
| 8.x, 9.x | eol | ended | — | 10 |
| 10.0–10.5 | eol | ended (10.5: 2026-07-01, when 11.4.0 shipped) | 8.1–8.4 | 12 |
| 10.6 | previous (security-only) | **2026-12-09** (Drupal 10 EOL) | 8.1–8.4 | 12 |
| 11.3 | previous (security-only) | 2026-12 (with 12.0.0) | 8.3–8.5 | 13 |
| 11.4 | **current** | 2027-06 | 8.3–8.5 | 13 |
| 11.5 | next minor, week of 2026-12-07 | | 8.3+ | 13 / 14 |
| 12.0 | dev (alpha1 2026-09-02; GA week of 2026-12-07) | | **8.5 only** | 14 (dev) |

Classes: `current` = newest stable minor of the newest GA major; `previous` = other minors still in
security support; `eol` = past end of life; `dev` = pre-release.

<!-- json:support -->
```json
{
  "last_reviewed": "2026-09-04",
  "branches": [
    {"branch": "7",    "class": "eol",      "eol": "2025-01-05", "php": "5.6-8.1", "drush": "8"},
    {"branch": "8",    "class": "eol",      "eol": "2021-11-02", "php": "7.0-8.0", "drush": "10"},
    {"branch": "9",    "class": "eol",      "eol": "2023-11-01", "php": "7.3-8.1", "drush": "11"},
    {"branch": "10.0", "class": "eol",      "eol": "2023-12-15", "php": "8.1-8.2", "drush": "12"},
    {"branch": "10.1", "class": "eol",      "eol": "2024-06-17", "php": "8.1-8.2", "drush": "12"},
    {"branch": "10.2", "class": "eol",      "eol": "2024-12-16", "php": "8.1-8.3", "drush": "12"},
    {"branch": "10.3", "class": "eol",      "eol": "2025-06-19", "php": "8.1-8.3", "drush": "12"},
    {"branch": "10.4", "class": "eol",      "eol": "2025-12-17", "php": "8.1-8.4", "drush": "12-13"},
    {"branch": "10.5", "class": "eol",      "eol": "2026-07-01", "php": "8.1-8.4", "drush": "12-13"},
    {"branch": "10.6", "class": "previous", "eol": "2026-12-09", "php": "8.1-8.4", "drush": "12-13"},
    {"branch": "11.0", "class": "eol",      "eol": "2025-06-19", "php": "8.3",     "drush": "13"},
    {"branch": "11.1", "class": "eol",      "eol": "2025-12-17", "php": "8.3-8.4", "drush": "13"},
    {"branch": "11.2", "class": "eol",      "eol": "2026-07-01", "php": "8.3-8.4", "drush": "13"},
    {"branch": "11.3", "class": "previous", "eol": "2026-12-09", "php": "8.3-8.5", "drush": "13"},
    {"branch": "11.4", "class": "current",  "eol": "2027-06-01", "php": "8.3-8.5", "drush": "13"},
    {"branch": "11.5", "class": "dev",      "eol": null,         "php": "8.3+",    "drush": "13-14"},
    {"branch": "12.0", "class": "dev",      "eol": null,         "php": "8.5",     "drush": "14"}
  ]
}
```
