---
name: drupal-runtime-verification
description: Use when about to run drush, composer, php, phpunit, phpcs, phpstan, or npm in a Drupal project, when verifying behaviour on a running site or in a browser, when Drupal MCP tools are present, or when deciding whether live verification is possible and how to state NOT VERIFIED.
---

# Drupal runtime verification

**Core principle:** a claim about behaviour is worth exactly the evidence level behind it. There are three levels; say which ones actually ran.

| Level | Evidence | Typical commands |
|---|---|---|
| **L1 static** | syntax, coding standards, static analysis | `php -l`, `phpcs --standard=<project or Drupal,DrupalPractice>`, `phpstan analyse` |
| **L2 Drupal automated** | tests and bootstrap | `phpunit -c <config> <path>`, `drush status`, `drush cr`, container compile, `drush updatedb:status` (pending updates, read-only; `drush updb` has no dry-run), `drush config:status` |
| **L3 live** | the running site | HTTP request (`curl -i`), `drush user:login` link + browser, logs (`drush watchdog:show`), real user flows |

## Procedure

1. Resolve commands, never hard-code them:

   ```bash
   "${CLAUDE_PLUGIN_ROOT}/scripts/drupal-runtime" . --summary
   ```

   `adapter: none` → run L1 with host tools if present; L2/L3 are impossible, so write `NOT VERIFIED — no runnable environment` and **offer** a disposable lab ([references/disposable-lab.md](references/disposable-lab.md)).

   > **Building a runtime needs the user's yes, every time.** Do not `composer create-project`, run `drupal-lab create`, download a core, pull an image, install Docker/DDEV, or assemble a scratch site anywhere (`/tmp` included) as a side effect of a verification request. In a non-interactive run you cannot get that yes, so the answer is always `NOT VERIFIED` plus the offer, never the lab. A lab the user did not ask for is a defect even when its test output is real: it costs them hundreds of megabytes, minutes, and a cleanup they did not choose. Once they agree, use `scripts/drupal-lab create` so the environment is marked, classified DISPOSABLE, and removable with one command.
2. Check `environment.class`. Anything but LOCAL/DISPOSABLE: read-only commands only; every state-changing command (`cr` included) needs the user's explicit approval for that environment before it runs; destructive commands are blocked by the guard hook regardless.
3. Run in order L1 → L2 → L3, project wrappers first (`composer test`, `make lint`). Record each as a ledger line:

   ```
   VERIFY L1 phpcs   PASS  "ddev exec vendor/bin/phpcs --standard=phpcs.xml.dist web/modules/custom/x"  0 errors
   VERIFY L2 phpunit FAIL  "ddev exec vendor/bin/phpunit -c web/core web/modules/custom/x/tests"  1 failure: FooTest::testBar
   VERIFY L3 http    NOT VERIFIED  adapter=none
   ```
   Full level definitions in [references/levels.md](references/levels.md).
4. **MCP present?** Look at your tool list for Drupal MCP fingerprints (`get_site_status`, `mcp_tools_list_available`, `tool_api__*`, `drupal_status`, `drupal_introspect`, `info`+`status`, `site_info`). Map them with [references/mcp-capabilities.md](references/mcp-capabilities.md): read-only tools may be called freely for introspection; write tools only with the same approval as the equivalent drush command; `drupal_drush`, `drupal_php_eval`, `drupal_sql_query`, `ddev_exec` are shell-equivalent and fall under the guard's rules. MCP never replaces the repository as the source of truth for code.
5. **Browser** ([references/browser.md](references/browser.md)): use the project's framework (Playwright, Nightwatch, Cypress) or the available browser tools for login, permissions, forms, AJAX, redirects, error pages; capture console errors, failed requests, HTTP status, and watchdog entries. Browser evidence supplements PHPUnit; it does not replace it.
6. **L3 on LOCAL / DISPOSABLE with a running adapter is expected, not optional**, for any change with user-visible behaviour: enable the module, create test users with Drush, exercise the feature through its own route or form, request as anonymous / without permission / with permission, compare two users on personalised pages, then read logs. Recipe and ledger lines in [references/live-verification.md](references/live-verification.md). "The module is not enabled" is a step in that recipe, not a `NOT VERIFIED` reason; the state change (enabled module, `drush cex` pending) goes into the handoff.
7. Read logs after L3: `drush watchdog:show --count=20 --severity=Error`, container/web server logs, browser console.

## Decision rules

- Run the narrowest thing that proves the claim (one test file, one URL), then the neighbours.
- A command that could not run is `NOT VERIFIED — <reason>`, never omitted and never "should work".
- `drush cr` is a verification step (container compiles) and a prerequisite for L3, not a fix.
- A red L1/L2 is a finding to report, not a reason to weaken the check.

## Works with process skills

`superpowers:verification-before-completion` owns the rule "no claim without fresh evidence"; this skill supplies the Drupal commands and the three-level ledger. `superpowers:finishing-a-development-branch` should run the project test command resolved here rather than `npm test`.

## Red flags

| Thought | Reality |
|---|---|
| "phpcs passed, so it works" | L1 proves style, not behaviour. |
| "I can't run it here, but it looks right" | Write `NOT VERIFIED — <reason>`; offer the lab. |
| "I'll just build a quick site in /tmp to run the test" | That is a lab, built without asking, and the user inherits the gigabytes and the cleanup. Offer first, then `scripts/drupal-lab create`. |
| "MCP says the module is enabled, so my code runs" | MCP shows site state; your code is proven by a test or a request. |
| "It's just drush cr on staging" | Staging is not LOCAL; announce and get approval. |
| "Enabling the module would create config drift, so L3 is NOT VERIFIED" | On LOCAL/DISPOSABLE, enable it, verify, and put `drush cex` in the handoff; drift is a note, not a blocker. |
| "I'll insert a row to see the page" | Verify through the feature's own route or service; a hand-written row proves nothing about the code. |
