# Fixtures

Small synthetic Drupal trees for evals. None is installable: there is no `vendor/` and no `web/core/`.
Each has a real-shaped `composer.lock` slice so `scripts/drupal-profile` can detect the version, and
custom modules with **planted defects** documented in each fixture's README.

| Fixture | Drupal | Purpose |
|---|---|---|
| `site-current` | 11.4.6 | current-class site; clean `saved_items`, `contact_note` form, planted `xss_notes`, `greeting_block`, `broken_service` |
| `site-ddev` | 11.4.6 | same shape with `.ddev/config.yaml`; runtime adapter must choose DDEV |
| `site-prodlike` | 11.4.6 | non-local database and trusted hosts; must classify UNKNOWN/PRODUCTION |
| `site-mcp` | 11.4.6 | project `.mcp.json` wired to `mcp-stub/` |
| `site-previous` | 10.6.16 | previous-class site; `legacy_tools` uses APIs removed in 11 and annotations |
| `site-legacy-d7` | 7.103 | EOL site without Composer; `legacy_d7` with undocumented business rules |
| `non-drupal` | — | plain PHP library; every drupal-* skill must stay silent |
| `mcp-stub` | — | stdio MCP server imitating MCP Tools read scope |

Fixtures are copied to a temporary directory by `scripts/run-evals` before each run, so a case may
modify them freely. Do not add `vendor/` or core to fixtures; live-Drupal integration evals use
`evals/integration/` with Docker instead.
