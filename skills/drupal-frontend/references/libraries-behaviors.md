# Libraries, Drupal.behaviors, once()

## `*.libraries.yml`
```yaml
global:
  version: 1.x
  css:
    theme:
      css/global.css: {}
  js:
    js/toggle.js: {}
  dependencies:
    - core/drupal
    - core/once
    - core/drupalSettings   # only if you pass settings
```
- CSS groups (`base`, `layout`, `component`, `state`, `theme`) control order; `{ minified: true }`, `{ preprocess: false }`, `{ attributes: { defer: true } }` per asset; external assets need `type: external` and a decision about privacy/SRI.
- Attach: theme-wide via `libraries:` in `*.info.yml`; per render array `'#attached' => ['library' => ['acme/global']]`; in Twig `{{ attach_library('acme/global') }}`; conditionally in preprocess.
- Override/extend core or module libraries: `libraries-override:` / `libraries-extend:` in the theme's info.yml, not by copying files.
- Aggregation and cache busting are automatic; bump `version:` when changing files if the project caches aggressively; `drush cr` after editing YAML.

## Behaviours
```js
((Drupal, once) => {
  Drupal.behaviors.acmeToggle = {
    attach(context) {
      once('acme-toggle', '.acme-toggle', context).forEach((button) => {
        button.addEventListener('click', () => {
          const panel = button.nextElementSibling;
          const open = panel.classList.toggle('is-open');
          button.setAttribute('aria-expanded', String(open));
        });
      });
    },
    detach(context, settings, trigger) {
      if (trigger === 'unload') {
        once.remove('acme-toggle', '.acme-toggle', context);
      }
    },
  };
})(Drupal, once);
```
- `attach(context, settings)` runs on page load and after every AJAX insert with `context` = the new fragment; without `once()` handlers bind twice.
- `once` needs the `core/once` dependency (core ≥ 9.2); `jQuery.once` is gone in 10.
- Settings: `'#attached' => ['drupalSettings' => ['acme' => ['limit' => 5]]]` → `settings.acme.limit` in `attach`; never `innerHTML` server data.
- Vanilla JS by default; jQuery only if the project already depends on `core/jquery`.
- ES modules / build: when the project has Vite/Webpack, edit sources and run the build; the library points at the built file.

## Common defects
| Pattern | Problem | Fix |
|---|---|---|
| `querySelectorAll` on `document` in attach without `once` | binds N times after AJAX | `once('id', selector, context)` |
| script in template | no aggregation, no dependency order, CSP | library + behaviour |
| missing `core/once` dependency | `once is not defined` | add dependency |
| `$(document).ready` | runs once, ignores AJAX | behaviour |
| behaviour with heavy work on `document` | slow on every AJAX | scope to `context` |

## Verification
- Browser: interaction works, no console errors, no duplicate handlers after an AJAX action (open a Views AJAX pager or an AJAX form), keyboard reachable.
- `drush cr`; check the library appears in the page source or network tab; aggregated on non-LOCAL environments.
- Core references: `core/misc/drupal.js`, `core/modules/toolbar/js/toolbar.js`, `core/themes/olivero/js/`.
