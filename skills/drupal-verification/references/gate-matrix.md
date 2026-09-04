# Gate matrix

Rows are classes of changed files; columns are gates. `req` = required (PASS/FAIL/NOT VERIFIED must appear), `opt` = include when a runtime exists, `—` = NOT APPLICABLE.

| Changed | Version detected | Code inspected | API verified | Standards (L1) | Static analysis (L1) | Tests (L2) | Bootstrap (L2) | Security review | Access review | Cacheability review | Live (L3) | Browser (L3) | Deployment notes |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| docs, README, comments | — | — | — | — | — | — | — | — | — | — | — | — |
| `*.info.yml`, `composer.json` | req | req | — | yml lint | — | opt | req (module enables) | — | — | — | — | — | req |
| `*.services.yml`, `*.routing.yml`, `*.permissions.yml`, `*.links.*.yml` | req | req | req | yml lint | opt | req | req (container/route rebuild) | routes: req | routes/permissions: req | — | opt | — | opt |
| `config/install`, `config/schema`, `config/optional` | req | req | — | yml lint | — | req (kernel config test or install) | opt | — | permissions config: req | — | — | — | req |
| PHP: controllers, forms, plugins, subscribers, services | req | req | req | req | req | req | opt | req | req when entity/user data | req when output/response | opt | opt when UI | opt |
| PHP: entity types, storage, access handlers | req | req | req | req | req | req (kernel) | req | req | req | req (entity cacheability) | opt | — | req (updb) |
| `.install`, `hook_update_N`, `post_update` | req | req | req | req | req | req (update test or kernel) | req (`updb -n`) | — | — | — | opt | — | req |
| Twig, `*.libraries.yml`, JS, CSS | req | req | opt | twig/eslint/stylelint | — | opt (FunctionalJavascript) | opt (cache rebuild) | escaping: req | — | req | opt | req when runtime | opt |
| tests only | req | req | — | req | opt | req (they run) | — | — | — | — | — | — | — |
| migrations YAML / process plugins | req | req | req | yml lint | req | req (migrate kernel) | req | — | — | — | opt | — | req |

Independent review (`drupal-code-reviewer` / `drupal-security-reviewer`) is required for architectural changes and for any row with a required security or access gate touched by more than a trivial edit; otherwise optional.

Report statuses: `PASS` (command ran, result confirms), `FAIL` (command ran, result contradicts), `NOT VERIFIED — <reason>` (could not run), `NOT APPLICABLE` (gate does not apply to this change), `NOT DONE` (review not performed).
