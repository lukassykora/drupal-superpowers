---
name: drupal-testing
description: Use when choosing a Drupal test level or writing PHPUnit Unit, Kernel, Functional, or FunctionalJavascript tests, Nightwatch, DTT, Behat, Cypress, or Playwright tests in a Drupal project; before implementing a feature or bugfix, when a Drupal test fails, or when phpunit.xml or SIMPLETEST_* setup is unclear.
paths:
  - "**/tests/**"
  - "**/phpunit.xml*"
---

# Drupal testing

**Core principle:** the cheapest test layer that exercises the real Drupal behaviour is the right one; a test is evidence only when it was run and would fail if the behaviour broke.

## When to use

Before implementing (test plan and RED test), when a test fails, when setting up PHPUnit for a project, when asked to add coverage. Not for browser-only verification (`drupal-runtime-verification`).

## Procedure

1. **Pick the level** with [references/test-levels.md](references/test-levels.md):
   - pure PHP logic with injected collaborators → **Unit** (`UnitTestCase`), only if the mocks stay few;
   - services, entities, config, plugins, queries, access handlers, hooks → **Kernel** (`KernelTestBase`), the default for module logic;
   - routes, permissions, forms, full page rendering → **Functional** (`BrowserTestBase`);
   - JS behaviours, AJAX, Drupal.behaviors → **FunctionalJavascript** (`WebDriverTestBase`);
   - project already uses DTT/Behat/Cypress/Playwright → follow the project for end-to-end, still write the PHPUnit test for the unit of behaviour.
2. **Set up the run** with [references/phpunit-setup.md](references/phpunit-setup.md): config file (`<docroot>/core/phpunit.xml.dist` or the project's), `SIMPLETEST_DB`, `SIMPLETEST_BASE_URL`, `BROWSERTEST_OUTPUT_DIRECTORY`; resolve the command via `drupal-runtime` (`ddev exec vendor/bin/phpunit …`, project `composer test`, …).
3. **Bugfix order**: write the regression test that encodes the correct behaviour → run → it must fail for the right reason (quote the assertion) → minimal fix → run → green → run the module's suite. If a regression test is not practical, say why in one sentence.
4. **Feature order**: test for the behaviour first (may be a Functional 403/200 pair for a route), then implement.
5. **Integrity** ([references/test-integrity.md](references/test-integrity.md)): never delete or loosen assertions to pass, never mock the thing under test, never mark skipped without a reason string, never count a test with zero assertions as evidence.
6. Record: `VERIFY L2 phpunit PASS|FAIL "<command>" <N tests, M assertions | failing test::method>`.

## Decision rules

- Kernel over Unit when the Unit test would mock more than two Drupal services.
- Functional over Kernel only when the route, permission, or rendered page is the behaviour.
- One test class per behaviour cluster; `@covers`/`#[CoversClass]` when the project uses them. Style follows the installed PHPUnit (`packages.phpunit` in the profile): 10.x pins PHPUnit 9, so annotations (`@group`); 11.x ships PHPUnit 11, so attributes (`#[Group]`, `#[RunTestsInSeparateProcesses]`).
- Long or noisy runs go to the `drupal-test-engineer` agent; it returns per-failure causes, not logs.
- Project frameworks: [references/project-frameworks.md](references/project-frameworks.md). CI gates and pipeline advice (only when asked or when a declared gate is missing): [references/ci-recommendations.md](references/ci-recommendations.md).

## Works with process skills

`superpowers:test-driven-development` owns the RED/GREEN/REFACTOR discipline; this skill supplies the Drupal level choice, setup, and integrity rules. In SDD briefs, name the level and the command so the implementer does not choose "Unit with mocks" by default.

## Red flags

| Thought | Reality |
|---|---|
| "Unit test with a mocked entity type manager, storage, query, and user" | That is a Kernel test in disguise; write the Kernel test. |
| "Functional tests are slow, skip them" | For a route + permission they are the only proof; run one, not all. |
| "The test fails because of the environment, adjust the expected value" | Fix the environment or the code; never the assertion. |
| "No SIMPLETEST_DB here" | Set it (sqlite works for Kernel: `sqlite://localhost/sites/default/files/.ht.sqlite`) or report NOT VERIFIED. |
| "I'll assert the page loads" | Assert the behaviour: status per role, the rendered value, the stored record. |
