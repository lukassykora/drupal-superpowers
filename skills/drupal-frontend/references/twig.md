# Twig in Drupal

## Escaping
- Autoescape is on: `{{ var }}` escapes strings; `MarkupInterface` objects (render results, `t()`, processed text) print as-is because they were sanitized upstream.
- `{{ content.body }}` renders the field through its formatter and text format; `{{ node.body.value }}` prints raw stored HTML escaped (looks broken) and `{{ node.body.value|raw }}` prints it unfiltered (XSS). Use the formatter.
- `|raw` only for `MarkupInterface` already produced by a sanitizer, with a comment; never on request data, field values, or user names.
- `|striptags`, `|escape('html_attr')`, `|clean_class`, `|clean_id` for specific contexts; `|render` when you need a string from a render array (rare; prefer `{{ }}`).
- `{{ attributes }}`, `{{ attributes.addClass('x') }}`, `{{ title_attributes }}`, `create_attribute()` for attribute bags; never concatenate attribute strings.

## Variables and preprocess
- Templates receive what `hook_theme` declares plus preprocess variables; `{{ dump() }}`/`{{ kint() }}` only with Twig debug on LOCAL.
- Preprocess in `<theme>.theme`: `hook_preprocess_HOOK(array &$variables)`; add `$variables['#cache']['tags'][] = ...` / `['contexts'][]` for anything fetched there (entity, config, current user).
- Prefer render arrays from modules over data fetching in preprocess; preprocess formats, it does not query.

## Suggestions
- `hook_theme_suggestions_HOOK_alter(array &$suggestions, array $variables)` adds e.g. `node__article__teaser`; core already provides `node--<bundle>--<view mode>.html.twig`. Check Twig debug comments for the list before inventing new ones.
- File naming: hook `node__article__teaser` → `node--article--teaser.html.twig`.

## Translation
- `{{ 'Read more'|t }}`, `{% trans %}Hello {{ name }}{% endtrans %}` (placeholders auto-escaped); `{{ 'Hello @name'|t({'@name': name}) }}`. Context: `|t({}, {'context': 'Long month name'})`.

## Includes, embeds, components
- `{% include '@acme/partials/card.html.twig' with {...} only %}` (`only` to avoid leaking scope); `{% embed %}` with blocks for wrappers; SDC: `{{ include('acme:teaser-card', {title: label}) }}` / `{% embed 'acme:teaser-card' with {...} %}{% block content %}...{% endblock %}{% endembed %}`.
- `attach_library('acme/global')` inside a template attaches a library for that render.

## Common defects to look for
| Pattern | Problem | Fix |
|---|---|---|
| `{{ x|raw }}` on field/user data | XSS | formatter / `processed_text` / escaped placeholder |
| `onclick=`, `<script>` in templates | not keyboard-accessible, CSP, no re-attach | behaviour + button |
| `<img src="...">` without `alt` | a11y failure | `alt` from field (`{{ content.field_image }}` handles it) or `alt=""` for decorative |
| `{{ node.field_x.value }}` | bypasses formatters, access, translations, cache | `{{ content.field_x }}` |
| `{% for %}` over `node.field_tags` loading entities | N+1 in template | preprocess or formatter |
| hard-coded strings | untranslatable | `|t` / `{% trans %}` |
| `file_url(node.field_image.entity.uri.value)` | ignores image styles and access | image formatter / `{{ content.field_image }}` or `image_style` via preprocess |

## Verification
- `drush cr` after adding templates or suggestions (theme registry).
- `drush twig:compile` (compiles every template; syntax errors surface) or the project's Twig linter (twigcs/twig-lint); Twig debug on LOCAL (`sites/development.services.yml`, `twig.config: debug: true`) shows which template rendered.
- Core references: `core/themes/olivero/templates/`, `core/modules/system/templates/`, `core/lib/Drupal/Core/Template/TwigExtension.php` (available filters/functions).
