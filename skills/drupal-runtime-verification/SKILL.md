---
name: drupal-runtime-verification
description: Use when about to run drush, composer, php, phpunit, phpcs, phpstan, or npm in a Drupal project, when verifying behaviour on a running site or in a browser, when Drupal MCP tools are present, or when deciding whether live verification is possible and how to state NOT VERIFIED.
---

# Drupal runtime verification

**Core principle:** a claim about behaviour is worth exactly the evidence level behind it. There are three levels; say which ones actually ran.

| Level | Evidence | Typical commands |
|---|---|---|
| **L1 static** | syntax, coding standards, static analysis | `php -l`, `phpcs --standard=<project or Drupal,DrupalPractice>`, `phpstan analyse` |
| **L2 Drupal automated** | tests and bootstrap | `phpunit -c <config> <path>`, `drush status`, `drush cr`, container compile, `drush updb --no-cache-clear -n` (dry), `drush config:status` |
| **L3 live** | the running site | HTTP request (`curl -i`), `drush user:login` link + browser, logs (`drush watchdog:show`), real user flows |

## Procedure

1. Resolve commands, never hard-code them:

   ```bash
   "${CLAUDE_PLUGIN_ROOT}/scripts/drupal-runtime" . --summary
   ```

   `adapter: none` → L1 with host tools if present, L2/L3 impossible → say `NOT VERIFIED — no runnable environment` and offer a disposable lab ([references/disposable-lab.md](references/disposable-lab.md)). Never install Docker/DDEV or start a stopped project without saying so.
2. Check `environment.class`. Anything but LOCAL/DISPOSABLE: read-only commands only, and every state-changing command (`cr` included) is announced before it runs; destructive commands need explicit approval for that environment (the guard hook blocks them otherwise).
3. Run in order L1 → L2 → L3, project wrappers first (`composer test`, `make lint`). Record each as a ledger line:

   ```
   VERIFY L1 phpcs   PASS  "ddev exec vendor/bin/phpcs --standard=phpcs.xml.dist web/modules/custom/x"  0 errors
   VERIFY L2 phpunit FAIL  "ddev exec vendor/bin/phpunit -c web/core web/modules/custom/x/tests"  1 failure: FooTest::testBar
   VERIFY L3 http    NOT VERIFIED  adapter=none
   ```
   Full level definitions in [references/levels.md](references/levels.md).
4. **MCP present?** Look at your tool list for Drupal MCP fingerprints (`get_site_status`, `mcp_tools_list_available`, `tool_api__*`, `drupal_status`, `drupal_introspect`, `info`+`status`, `site_info`). Map them with [references/mcp-capabilities.md](references/mcp-capabilities.md): read-only tools may be called freely for introspection; write tools only with the same approval as the equivalent drush command; `drupal_drush`, `drupal_php_eval`, `drupal_sql_query`, `ddev_exec` are shell-equivalent and fall under the guard's rules. MCP never replaces the repository as the source of truth for code.
5. **Browser** ([references/browser.md](references/browser.md)): use the project's framework (Playwright, Nightwatch, Cypress) or the available browser tools for login, permissions, forms, AJAX, redirects, error pages; capture console errors, failed requests, HTTP status, and watchdog entries. Browser evidence supplements PHPUnit; it does not replace it.
6. Read logs after L3: `drush watchdog:show --count=20 --severity=Error`, container/web server logs, browser console.

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
| "MCP says the module is enabled, so my code runs" | MCP shows site state; your code is proven by a test or a request. |
| "It's just drush cr on staging" | Staging is not LOCAL; announce and get approval. |
