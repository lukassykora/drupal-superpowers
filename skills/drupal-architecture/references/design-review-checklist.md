# Design review checklist

Run only the rows whose "applies when" holds. Each row is a question with the artefact that answers it.

| Row | Applies when | Question | Answer must name |
|---|---|---|---|
| Security | any input, output, file, query, redirect, or external call | Where does untrusted data enter and leave? | escaping points, validation, allow-lists (`drupal-security`) |
| Access | entities, user data, admin features, new routes | Who may see/change this, and where is that enforced? | route requirement, entity access handler/hook, field access, query `accessCheck` |
| Cacheability | any render output or response | What does the output vary by and when does it change? | cache contexts, tags, max-age; lazy builder for the personalized part (`drupal-cacheability`) |
| Configuration | new settings, types, presets | What deploys vs what stays per environment? | config vs state vs settings.php; schema file; default config in `config/install` |
| Translations / multilingual | site is multilingual or strings are user-facing | Are strings and entities translatable? | `t()`/`TranslatableMarkup`, `translatable: true` on entity/field, language cache context |
| Revisions / moderation | content entities on a site with content_moderation or workspaces | Does the change respect revisions and workflow states? | revisionable fields, `latest_revision` handling, moderation state access |
| Deployment | schema, config, data, or index changes | What must run on deploy and in which order? | `hook_update_N`, `post_update`, config export, `drush cr`, reindex; the project's own deploy order |
| Backward compatibility | contrib-shaped module or public API | Who else calls this? | deprecation path, BC layer, `core_version_requirement` |
| Testability | always for bounded/architectural | Which test level proves the behaviour? | Kernel/Functional test plan (`drupal-testing`) |
| Performance | loads many entities, runs on every request, or calls external services | What is the cost per request and how is it bounded? | query limits, caching, queue, lazy builders (`drupal-performance` in Phase 2) |
| Scope | always | What is deliberately not changed? | list of adjacent problems reported separately |
