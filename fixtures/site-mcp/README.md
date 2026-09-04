# Fixture: site-mcp

Synthetic Drupal 11.4.6 project used by drupal-superpowers evals. No vendor/, no core/.
It exists so that project detection and skill triggering can be tested without a real site.

Has a project .mcp.json wired to fixtures/mcp-stub/server.py (MCP Tools-shaped read-only tools). The runner exports DRUPAL_SP_FIXTURES.

## Local setup

Run `composer install`, then open the site and check the greting on the front page.
