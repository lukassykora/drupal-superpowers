# Browser verification

Use the project's own browser framework when it exists (Playwright, Cypress, Nightwatch, Behat with a JS driver); otherwise the browser tools available in the session (Claude in Chrome, Playwright MCP). Browser evidence supplements PHPUnit; it never replaces a Functional test.

## What to verify in a browser

| Scenario | Steps | Evidence to capture |
|---|---|---|
| Login and permissions | `drush user:login --uri=<site_url> --name=<user>` for a one-time link (local only); visit the protected path as anonymous, unprivileged, privileged | HTTP status per role, visible/hidden UI elements |
| Forms | fill, submit invalid then valid input | validation messages, redirect target, stored result |
| AJAX / JS behaviours | trigger the interaction; watch for `Drupal.behaviors` re-attachment and `once()` idempotence | DOM change, no duplicate handlers, no console errors |
| Admin UI | the config form or listing the change adds | form saves; config exported afterwards matches |
| Content workflows | create → moderate → publish | states and access at each step |
| Redirects and error pages | request old/removed paths | 301/302/404/403 as designed |
| Rendering / theming | the page in the project's theme, default viewport and a narrow one | screenshot, no layout errors, no missing library |
| Cache correctness | same URL as two users; `X-Drupal-Dynamic-Cache` header | no cross-user content |

## Always capture

- Console errors and warnings (JS exceptions, missing assets).
- Failed or slow network requests (4xx/5xx, blocked AJAX).
- HTTP status and Drupal cache headers of the main document.
- New watchdog entries after the flow: `drush watchdog:show --count=20`.
- Accessibility quick check when the change adds UI: labels present, focus order sensible, keyboard reachable, contrast not obviously broken (use the accessibility tree when the tool offers it).

## Rules

- Local or disposable environments only for authenticated flows and any data-changing action. On other environments, read-only anonymous checks at most, announced first.
- Never paste credentials into transcripts; use one-time login links or the project's test users.
- Record the result as `VERIFY L3 browser PASS|FAIL|NOT VERIFIED "<flow>" <evidence>`.
