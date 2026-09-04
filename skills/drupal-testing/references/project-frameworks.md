# Project-specific test frameworks

Use only what the project already has (profile field `testing_frameworks`). Never add a framework to run one check.

| Framework | Detect | Run | Use for | Notes |
|---|---|---|---|---|
| Drupal Test Traits (DTT) ExistingSite | `weitzman/drupal-test-traits` in lock; `tests/src/ExistingSite`; `phpunit.xml` with `DTT_BASE_URL` | `vendor/bin/phpunit -c <dtt config> tests/src/ExistingSite` | tests against the real installed site (config, content types) without reinstalling | cleanup via `markEntityForCleanup()`; runs against the current DB, so LOCAL/DISPOSABLE only |
| Behat (Drupal Extension) | `behat.yml`, `drupal/drupal-extension` | `vendor/bin/behat --tags=@x` | end-to-end scenarios in business language | needs a running site; Mink driver config in `behat.yml` |
| Cypress | `cypress.config.*`, `package.json` scripts | `npx cypress run --spec ...` | JS-heavy front-end flows | base URL from config; login helpers usually custom |
| Playwright | `playwright.config.*`, `@playwright/test` | `npx playwright test <spec>` | browser flows, accessibility snapshots | also usable ad hoc for L3 evidence |
| Nightwatch | `nightwatch.conf.js`, `core/tests/Drupal/Nightwatch` | `yarn test:nightwatch` from core | core-style JS tests | rarely in projects |
| Codeception / other | project files | project script | | follow project docs |

Rules:
- PHPUnit remains the unit of behaviour proof; project frameworks add end-to-end evidence (L3).
- Environment classification applies: frameworks that log in and change data run only on LOCAL/DISPOSABLE.
- Report each run as `VERIFY L2|L3 <framework> PASS|FAIL "<command>" <summary>`.
