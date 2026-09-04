# Source-of-truth hierarchy and how to query each source

| Rank | Source | How to query | Notes |
|---|---|---|---|
| 1 | Installed core source of this version | `drupal-lookup <symbol>`; `grep -rn "function name(" <docroot>/core`; read `*.api.php` for hooks | Highest authority. Includes `@deprecated` docblocks and `trigger_error(... E_USER_DEPRECATED)` |
| 2 | Drupal AI Best Practices skills | project `.agents/skills/drupal-*` if synced; https://www.drupal.org/project/ai_best_practices | Community-canonical guidance (GPL; cite, do not copy) |
| 3 | api.drupal.org for the matching branch | `https://api.drupal.org/api/drupal/<branch>/search/<symbol>` (HTML only; no JSON) | Branch segment must match (10.x / 11.x / main) |
| 4 | Change records | `https://www.drupal.org/api-d7/node.json?type=changenotice&field_project=3060&field_change_to_branch=<branch>`; single record `node/<nid>.json`; https://www.drupal.org/list-changes/drupal | Exact-title filters only; use keywords in the HTML list |
| 4b | Drupal Code Query (third-party) | `https://api.tresbien.tech/v1/symbol/search?q=<symbol>`, `/v1/symbol/<id>` (lifecycle), `/v1/search/code?q=<regex>` (core + contrib usage), `POST /v1/composer/scan` | Rate-limited, no auth; convenience, not a dependency |
| 5 | Official developer docs | https://www.drupal.org/docs/develop | Conceptual; not version-precise |
| 6 | Coding standards | https://project.pages.drupalcode.org/coding_standards/ | Applies regardless of core version |
| 7 | Security documentation | https://www.drupal.org/docs/security-in-drupal, https://www.drupal.org/security | |
| 8–9 | Contrib module docs, then its source | `vendor/drupal/<module>` or `<docroot>/modules/contrib/<module>` | Source beats README |
| 10 | drupal.org issues / GitLab | `https://www.drupal.org/api-d7/node.json?type=project_issue&field_project=<id>` | Evidence of bugs, not of API |
| 11 | Trusted community material | Drupalize.me, maintainer blogs | Confirm against 1–4 |
| 12 | General web | | Last resort, never authoritative |

Rule: a lower-ranked source can raise a question; only a higher-ranked source settles it.
