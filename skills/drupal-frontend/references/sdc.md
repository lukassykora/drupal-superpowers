# Single Directory Components (SDC)

Available in core from 10.1 (experimental), stable in 10.3+; verify with `drupal-facts` / the installed `core/modules/sdc` or `core/lib/Drupal/Core/Theme/Component`. Use them when the project already has `components/` in a theme or module; do not introduce SDC into a project that renders through templates only without a design decision.

## Layout
```
components/teaser-card/
  teaser-card.component.yml   # metadata + prop schema (required)
  teaser-card.twig            # markup
  teaser-card.css             # optional, auto-attached
  teaser-card.js              # optional, auto-attached (write as a Drupal.behavior)
  README.md
```

## Metadata
```yaml
$schema: https://git.drupalcode.org/project/drupal/-/raw/HEAD/core/assets/schemas/v1/metadata.schema.json
name: Teaser card
status: stable          # experimental | stable | deprecated | obsolete
group: Cards
props:
  type: object
  required: [title]
  properties:
    title:
      type: string
      title: Title
    url:
      type: string
      format: uri
      title: Link
    variant:
      type: string
      enum: [default, compact]
      default: default
slots:
  content:
    title: Content
libraryOverrides:
  dependencies:
    - core/once
```
- Schemas are enforced always for module components and for theme components when the theme's info.yml sets `enforce_prop_schemas: true`; props are validated at render time only with Twig `debug: true`. Declare `required` and types; slots carry render arrays/markup.
- Components are discovered per theme/module; `replaces:` lets a theme override a module component.

## Using
- Twig: `{{ include('acme:teaser-card', { title: label, url: url }, with_context = false) }}`; with slots: `{% embed 'acme:teaser-card' with { title: label } only %}{% block content %}{{ content.body }}{% endblock %}{% endembed %}`.
- Render array: `['#type' => 'component', '#component' => 'acme:teaser-card', '#props' => [...], '#slots' => ['content' => $build]]`.
- Props are escaped like any Twig variable; slots receive render arrays (cacheability bubbles).

## Rules
- No data fetching or business logic in components; the caller supplies props.
- Accessibility inside the component (headings, buttons, labels) is the component's responsibility; document keyboard behaviour in the README.
- Name components by purpose, keep variants as an `enum` prop instead of new components.
- Test: a `KernelTestBase` test that renders `['#type' => 'component', …]` through the renderer and asserts markup (core's own tests: `core/tests/Drupal/KernelTests/Components/`), or a Functional test; visual check in the browser.

Core references: `core/modules/sdc` (10.1–10.2) / `core/lib/Drupal/Core/Theme/Component` (10.3+), `core/themes/olivero/components/`? (check the installed version; Olivero adopted SDC gradually), `core/modules/system/tests/modules/sdc_test/components/`.
