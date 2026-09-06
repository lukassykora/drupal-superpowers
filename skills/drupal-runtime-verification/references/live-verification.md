# Live verification (L3) on a local or disposable runtime

**When this applies:** `drupal-runtime` reports `environment.class` LOCAL or DISPOSABLE and the adapter is running (DDEV, Lando, Compose, native). Anything else (UNKNOWN, STAGING, PRODUCTION) stays read-only and gets `VERIFY L3 … NOT VERIFIED — environment <class>` plus the offer to run it where it is safe.

**Core principle:** on a local runtime, a change that has a user-visible behaviour is verified against the running site, with a real request, as the real kind of user. "The module is not enabled, so I did not check" is not a reason: enable it, check, and report the state change.

## Procedure

1. **Say what you will change on the site** before doing it (one line): module enable, test user, test content, cache rebuild. On LOCAL that announcement is enough; on DISPOSABLE nothing needs announcing beyond the ledger line.
2. **Prepare the state with Drush, never with SQL:**
   - module: `<drush> pm:enable <module> -y` (then note that `core.extension` now differs from `config/sync`; on a site that exports config, add `drush cex` to the handoff instead of exporting yourself)
   - users: `<drush> user:create dsp_test_<role> --password=<random>` and `<drush> user:role:add <role> dsp_test_<role>`; one user per permission level you need to compare
   - content: create through the entity API in `<drush> php:eval` or with `devel_generate` when present; use the feature's own route or service to produce its data (save an item through the save route, not by inserting a row)
   - `<drush> cr` after routes, services, hooks, or libraries changed
3. **Request as each user class** and record status, cache headers, and a body excerpt:

   ```bash
   # site URL from the runtime adapter, e.g. https://myproj.ddev.site
   curl -sSi "<site_url>/user/3/saved"                                  # anonymous → 403 or 302 to /user/login
   LINK=$(<drush> user:login --uri=<site_url> --name=dsp_test_owner --no-browser)   # one-time login link
   curl -sS -c /tmp/dsp-cookies -L "$LINK" -o /dev/null                  # establishes the session cookie jar
   curl -sSi -b /tmp/dsp-cookies "<site_url>/user/3/saved"              # owner → 200, body contains the saved title
   ```

   With DDEV, `curl` from the host reaches `https://<name>.ddev.site`; inside the container use `ddev exec curl -sSi http://localhost/...`. Lando and Compose: the adapter's `site_url`.
4. **Cache correctness:** request the same personalised page as two different users; the personalised part must differ and `X-Drupal-Dynamic-Cache`/`X-Drupal-Cache` must not serve one user's copy to the other. A page that varies per user but shows `HIT` for the second user without the `user` context is a leak.
5. **Logs:** `<drush> watchdog:show --count=20 --severity=Error` after the requests; new entries attributable to the change are findings.
6. **Clean up what you created** (`<drush> user:cancel --delete-content dsp_test_owner -y`, content deletion through the API, `pm:uninstall` only if you enabled the module and the user did not ask for it to stay) and say what remains (an enabled module on a LOCAL site usually stays; report it in the handoff with the `drush cex` step).
7. **Ledger lines**, one per check:

   ```
   VERIFY L3 http 403-anon   PASS "curl -sSi https://x.ddev.site/user/3/saved" 403
   VERIFY L3 http 200-owner  PASS "curl -b cookies https://x.ddev.site/user/3/saved" 200, 'Article 7' in body
   VERIFY L3 cache per-user  PASS two users, different bodies, no HIT crossover
   VERIFY L3 watchdog        PASS 0 new errors
   ```

## Decision rules

- Verify through the feature's own surface (route, form, service), not by editing tables; a row written by hand proves nothing about the code.
- One request per claim in the report; if a claim has no request behind it, it is `NOT VERIFIED`.
- Browser tools or the project's Playwright/Nightwatch/Cypress setup ([browser.md](browser.md)) replace `curl` for JS behaviours, AJAX, and admin UI; they do not replace PHPUnit.
- Do not enable modules, create users, or rebuild caches on UNKNOWN/STAGING/PRODUCTION; ask, or report `NOT VERIFIED`.
