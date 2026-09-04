# Deployment impact

Never invent a universal order; read the project's (`deployment.hints` in the profile: CI file, Makefile, composer scripts, `drush deploy`). Default Drupal sequence when nothing is defined: `composer install --no-dev` → `drush updb -y` → `drush cim -y` → `drush cr` (`drush deploy` runs updb, cim, cr, deploy:hook in that order).

## What a change requires
| Change | Deploy step | Notes |
|---|---|---|
| PHP code only | code deploy + `drush cr` | container/plugin caches |
| New service / route / plugin | `drush cr` | included in `drush deploy` |
| New module | `drush pm:enable` via `core.extension.yml` import (`cim`) | module must be present in code first |
| Schema change (`hook_schema`, base field) | `hook_update_N` → `drush updb` | before `cim` |
| Data change (backfill, reformat) | `hook_post_update_NAME` → `drush updb` | batchable via `$sandbox` |
| Config change (settings, fields, views, roles) | export locally → commit → `drush cim` on deploy | export before import if prod editors change config |
| Permission changes | config (`user.role.*`) → `cim` | check `restrict access` |
| Content/entity type definition change | `hook_update_N` with entity definition update manager | not automatic |
| Search index changes | reindex (`drush search-api:index`, `drush search-api:reset-tracker`) | after cim |
| Queue/cron additions | ensure cron runs; queue workers picked up after `cr` | |
| Translations | `drush locale:update`/`locale:import` if the project uses them | |
| Node access changes | `node_access_rebuild()` (batch) — expensive | schedule |
| Cache tag semantics changed | `drush cr` | |

## Deploy hooks (Drush 10.3+)
`hook_deploy_NAME()` in `<module>.deploy.php` runs after `cim` via `drush deploy:hook`; use for content-dependent changes that need imported config.

## Report format
```
Deployment:
- drush updb (saved_items_update_11401: new column saved_items.created)
- drush cim (user.role.editor: +use saved items)
- drush cr
- reindex not needed
```
