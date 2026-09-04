---
name: drupal-frontend
description: Use when working in a Drupal theme or on front-end output: Twig templates, *.libraries.yml, Drupal.behaviors and once(), preprocess functions, theme suggestions, Single Directory Components, CSS/JS assets, or accessibility of rendered markup; and when reviewing theme code.
paths:
  - "**/themes/custom/**"
  - "**/*.twig"
  - "**/*.libraries.yml"
  - "**/*.theme"
  - "**/components/**"
---

# Drupal frontend

**Core principle:** the theme layer renders what the render system hands it, escaped by default, attached through libraries, and behaving through `Drupal.behaviors`. Every shortcut around those three (raw output, inline handlers, global scripts) is a defect, not a style choice. Use the project's design system and components before adding new ones.

## When to use

Twig templates and preprocess, theme libraries and assets, JS behaviours, SDC components, theme suggestions, markup accessibility, theme-level review. Not for module render arrays and blocks (`drupal-module-development`, `drupal-cacheability`) except where the template consumes them.

## Procedure

1. **Orient in the theme**: base theme, regions, `*.libraries.yml`, existing components (`components/`), build tooling (profile `frontend.*`: package manager, Vite/Webpack), CSS conventions (BEM, utility classes), existing `templates/` naming. Follow what exists.
2. **Templates** ([references/twig.md](references/twig.md)): autoescape stays on; render fields with `{{ content.field }}` or `{{ content|without(...) }}`, never `|raw` on field or user data; attributes through `{{ attributes }}`/`addClass()`; `{% trans %}` or `{{ 'text'|t }}` for UI strings; suggestions via `hook_theme_suggestions_HOOK_alter` or `hook_preprocess_HOOK`, not copied templates for every bundle.
3. **Preprocess** (`*.theme`): compute variables, inject nothing static (`\Drupal::service()` is tolerated in `.theme` files but prefer render arrays built by the module, and add cacheability for anything fetched: `$variables['#cache']['contexts'|'tags']`).
4. **Assets** ([references/libraries-behaviors.md](references/libraries-behaviors.md)): every JS/CSS goes through `*.libraries.yml` and is attached (`#attached`, `{{ attach_library() }}`, `libraries:` in info.yml for global); JS is a `Drupal.behaviors.<name>.attach(context, settings)` using `once('name', selector, context)`; data to JS via `drupalSettings`, never inline `<script>` or `onclick`.
5. **SDC** ([references/sdc.md](references/sdc.md)) when the project uses components: `*.component.yml` schema with required props, `{{ include('theme:component', {...}) }}` or `{% embed %}`, slots for markup, no business logic in components.
6. **Accessibility** ([references/accessibility.md](references/accessibility.md)) for any UI change: semantic elements, one `h1`, labels for controls, `alt` on images (empty for decorative), keyboard operability for anything clickable (button/link, not div+onclick), focus visible, ARIA only where native semantics fall short, contrast ≥ 4.5:1 for text.
7. **Verify**: `drush cr` for Twig/theme-registry changes; Twig lint (`drush twig:lint` or project lint); build step if any; browser check when a runtime exists (`drupal-runtime-verification` browser reference): console errors, behaviour re-attach after AJAX (no double binding), keyboard walk, accessibility tree; cache headers unchanged for anonymous.

## Decision rules

- Output HTML from data? Only via `Markup` produced by a text format (`processed_text`) or escaped placeholders; `|raw` needs a comment naming the sanitizer upstream.
- Interactive element → `<button>` or `<a href>`; `div`/`span` with a click handler is a defect.
- One template per real variation; suggestions and preprocess handle the rest.
- New CSS/JS → the project's build pipeline and library; no CDN links without an explicit decision (privacy, SRI).
- Component vs template: SDC when the project has `components/` or SDC in core (10.1+, stable in 10.3+); otherwise templates + preprocess.

## Works with process skills

Design-time: fills the UI rows of `drupal-architecture`'s review. Long UI work with browser tooling goes to the `drupal-frontend-specialist` agent; reviews use this skill as the theme lens next to `drupal-security` (escaping) and `drupal-cacheability`.

## Red flags

| Thought | Reality |
|---|---|
| "`|raw` is fine, the body is already filtered" | Then render `content.body` (processed_text) and let the format decide; raw skips every future filter change. |
| "A quick inline onclick" | Not keyboard operable, not re-attachable, blocked by CSP; use a behaviour and a button. |
| "One global script in `html.html.twig`" | Libraries aggregate, cache, and order dependencies; inline scripts do none of that. |
| "Copy the template for each content type" | Suggestions already exist; a preprocess variable beats ten templates. |
| "ARIA fixes it" | Native elements first; ARIA on a div is a last resort and needs keyboard handling too. |
