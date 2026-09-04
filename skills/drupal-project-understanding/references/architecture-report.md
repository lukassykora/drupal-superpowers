# Project architecture report

For onboarding, a handover, or an audit, the Project Capability Profile becomes a readable report. Produce it only when asked; it is not part of ordinary task work. Everything in it is evidence from files or read-only commands, never memory.

## Assemble

```bash
"${CLAUDE_PLUGIN_ROOT}/scripts/drupal-profile" . --no-cache      # full JSON
"${CLAUDE_PLUGIN_ROOT}/scripts/drupal-runtime" .                 # adapter + environment class
```
Plus, read-only: `drush pm:list --status=enabled --format=json`, `drush config:status`, `drush core:requirements --severity=1`, `composer show --direct`, `composer audit`, `drush pm:security`, the CI file, `README`/`CLAUDE.md`, and the custom module list with line counts (`find … -name '*.php' | xargs wc -l`).

## Report shape

```markdown
# <Project> — Drupal architecture report (<date>)

## 1. Platform
Drupal <version> (<router class>, EOL <date>) · PHP <constraint>/<runtime> · DB <driver> · Drush <version>
Distribution: core | Drupal CMS | profile:<name> · Multisite: yes/no · Languages: <n>
Runtime: <adapter> (<environment class>) · CI: <gitlab|github|none> · Deployment: <hints>

## 2. Code map
| Area | What | Size | Notes |
|---|---|---|---|
| custom modules | <list> | <files/LOC> | owners, purpose, test coverage |
| custom themes | <list> | | base theme, build tooling, SDC |
| contrib | <n> modules | | patched: <list>; security advisories: <n> |
| recipes/profiles | | | |

## 3. Content model
Entity types and bundles in use, fields per bundle that matter, revisions/moderation/workspaces, translations, media, taxonomy. Source: config export (`node.type.*`, `field.field.*`) or read-only MCP/Drush introspection.

## 4. Configuration and environments
Config sync directory, config split, settings.php overrides per environment, secrets mechanism, what deploys vs what does not.

## 5. Quality and verification
phpcs/phpstan config and current status, test suites present and what they cover, CI jobs, what is NOT covered.

## 6. Risks (ranked)
| Risk | Evidence | Impact | Suggested action |
|---|---|---|---|
| Drupal <version> reaches EOL <date> | matrix | site unsupported | plan upgrade, see drupal-upgrade |
| <n> contrib modules without a release for the next major | contrib-info | blocks upgrade | evaluate replacements |
| custom module X has no tests | file listing | regressions invisible | add Kernel tests |
| security advisories: <n> | composer audit / pm:security | | patch |

## 7. Verified / not verified
VERIFY lines for every command that ran; explicit NOT VERIFIED for anything the environment did not allow.
```

## Rules

- Every number comes from a command whose output you saw; no estimates presented as facts.
- Risks are ranked by consequence, with the evidence next to each.
- No recommendations that require rewriting the project; suggest the smallest next step per risk.
- Never include secrets, credentials, hostnames of production systems, or customer data.
