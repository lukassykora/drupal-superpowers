---
name: drupal-security
description: Use when Drupal code handles access or permissions, routes, entity or user data, query parameters, output or Markup, Twig, file uploads, database queries, redirects, AJAX callbacks, or trusted callbacks, and when asked for a security audit or review of Drupal code.
user-invocable: true
argument-hint: "[module or path]"
model: opus
effort: high
---

# Drupal security

**Core principle:** security is a property of the design (who may do what, where untrusted data enters and leaves) and is checked at every layer Drupal provides, not added as a final pass. Findings are classified, not just listed.

## When to use

Writing or changing code in the categories above; explicit audits (`/drupal-superpowers:drupal-security`); reviewing a change that touches them. For independent review of someone else's implementation, dispatch the read-only `drupal-security-reviewer` agent with this skill's checklist.

## Procedure

1. **Map the data flow**: entry points (routes, forms, AJAX, CLI, queues, imports), stored data (entities, config, state, files), outputs (render arrays, Twig, JSON, mail, logs, redirects), external calls.
2. **Access at every layer** ([references/access-patterns.md](references/access-patterns.md)): route requirement → entity access (`->access()`, `accessCheck(TRUE)`) → field access → operation-specific checks. `load()`/`loadMultiple()`/entity query without access check followed by output is a finding until proven otherwise.
3. **Output escaping** ([references/output-escaping.md](references/output-escaping.md)): `Markup::create()`, `#markup` with concatenated variables, `|raw`, `Xss::filterAdmin` on user input, `FormattableMarkup` with `!`/unsafe placeholders, `#allowed_tags` misuse. Twig autoescape is the default; anything that bypasses it needs a stated reason.
4. **Run the checklist** in [references/checklist.md](references/checklist.md): authentication, authorization/permissions, CSRF, XSS, SQL, SSRF, path traversal, file uploads/private files, command execution, deserialization, open redirects, information disclosure, secrets/logging, API/AJAX access, cache leaks, trusted callbacks, dependency advisories (`composer audit`; Drush ≤ 12 also `drush pm:security`).
5. **Classify each finding**: `CONFIRMED` (exploit path shown with file:line), `PROBABLE` (pattern present, exploitability not proven), `DEFENSE-IN-DEPTH` (no exploit, but a missing layer), `FALSE POSITIVE` (looks risky, is safe, with the reason). Severity CRITICAL/HIGH/MEDIUM/LOW.
6. **Fix Drupal-natively**: route requirements, `AccessResult` with cacheability, `t()`/placeholders/`#plain_text`, `Html::escape`, database placeholders/query builder, `UrlHelper::isExternal`/`TrustedRedirectResponse` only for trusted hosts, `#upload_validators` with validator plugin IDs (`FileExtension`, `FileSizeLimit`) / the `file.validator` service (10.2+; the procedural `file_validate_*()` functions were removed in 11.0), private scheme, `TrustedCallbackInterface`. Then add the test that proves the fix (403 for the wrong user; escaped output).
7. Report findings first, sorted by severity, then fixes and evidence.

## Decision rules

- Cache metadata is part of access: a permission-dependent output without `user.permissions` (or `user`) context is a cache leak, severity HIGH.
- `_access: 'TRUE'` on a route that reads entities or user data is CONFIRMED HIGH unless the data is public by design (say so).
- Contrib modules: check the security advisory policy coverage and `composer audit`; report advisories, do not upgrade unrequested.
- Never print secrets, tokens, or credentials in findings; refer to their location.
- Facts about which core API escapes what come from the installed core (`drupal-research`), not memory.

## Works with process skills

Inside `superpowers:brainstorming`, this supplies the security rows of the design review. For reviews, the `drupal-code-reviewer`/`drupal-security-reviewer` agents receive the diff as a file and this checklist as their lens.

## Red flags

| Thought | Reality |
|---|---|
| "Only admins will use this route" | Then require the permission; roles change, routes stay. |
| "`#markup` is fine, Drupal filters it" | `#markup` runs `Xss::filterAdmin`, which allows many tags; `Markup::create()` filters nothing. |
| "The value comes from the database, it's safe" | Stored XSS exists; escape on output every time. |
| "It's an internal AJAX endpoint" | Routes are public; require permission and CSRF as appropriate. |
| "I'll log the request for debugging" | Request bodies contain credentials; log identifiers only. |
