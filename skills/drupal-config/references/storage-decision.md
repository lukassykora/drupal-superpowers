# Where does a value live?

| Question (answer yes to the first that fits) | Storage | API | Exported? |
|---|---|---|---|
| Is it a secret or a connection detail (API key, DB host, SMTP password)? | environment → `settings.php` `$settings[...]`/`$config[...]` overrides, or Key module | `Settings::get()`, `$config` overrides | never |
| Does it differ per environment on purpose (error level, caching, external endpoints per env)? | `settings.<env>.php` override or Config Split | `$config['module.settings']['key'] = ...;` | overrides not exported; split exports per environment |
| Is it site structure or a definition editors/site builders manage and every environment must share (content types, fields, views, roles, module settings, presets)? | **config** (simple config or config entity) | `\Drupal::config()`, `configFactory->getEditable()`, config entities | yes (`drush cex`) |
| Is it user-generated or business data (articles, orders, submissions, saved lists)? | **content** (content entity, or a custom table behind a service if truly non-content) | Entity API | never (migrations for moving) |
| Is it runtime bookkeeping that must not deploy (last cron run, last import timestamp, feature flag toggled per env, OAuth expiry)? | **State** | `\Drupal::state()->get/set()` | never |
| Is it per-user or per-session draft/wizard data? | **PrivateTempStore** / **SharedTempStore** | `tempstore.private`/`tempstore.shared` | never; expires |
| Can it be recomputed from other data? | **Cache** bin (with tags) or key/value with expiry | `cache.<bin>`, `keyvalue.expirable` | never |
| Is it a translation of config? | config translation (`language/<lang>/…`) | `config_translation` UI; overrides live in the `language.<langcode>` collection (sync: `language/<langcode>/<name>.yml`) | yes |

Consequences to state in the design:
- Config changes need export + import in the deploy; content changes need migrations or editorial work; state changes need nothing but must be set per environment (an update hook or post-update may seed it).
- A config value edited on production and not exported will be reverted by the next import unless the deploy process exports first: say so when a feature invites editors to change config.
- Config entities give you listing, dependencies, UUIDs, and translation for free; simple config is a single YAML object with schema.
