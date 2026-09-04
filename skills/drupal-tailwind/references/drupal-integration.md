# Tailwind against Drupal's own CSS, admin UI, and aggregation

## Preflight reaches Drupal's chrome

Preflight removes all margins, sets `box-sizing: border-box` and `border: 0 solid` on every element, unstyles `h1`–`h6`, removes list markers from `ol`/`ul`/`menu`, and makes `img`/`svg`/`video`/`canvas` block-level (<https://tailwindcss.com/docs/preflight>). In Drupal that lands on the toolbar, contextual links, Layout Builder affordances, and every admin page the front-end theme renders.

Four strategies are in real Drupal use. They compose; pick deliberately rather than patching symptoms later.

**1. Prefix every utility.** The oldest Drupal-specific argument: "By adding a prefix like `tw-`, we can ensure that the Tailwind classes don't conflict with core HTML classes like `block`" (<https://www.oliverdavies.uk/blog/using-tailwind-css-your-drupal-theme>). `block`, `field`, `button`, `item-list`, `hidden` are all Drupal class names. In v4 the prefix moves into the import: `@import "tailwindcss" prefix(tw)`.

**2. Drop Preflight.** v3 used `corePlugins: { preflight: false }`. v4 has no `corePlugins`; the documented method is to import the parts and omit preflight:

```css
@layer theme, base, components, utilities;
@import "tailwindcss/theme.css" layer(theme);
@import "tailwindcss/utilities.css" layer(utilities);
```
<https://tailwindcss.com/docs/preflight>

**3. Patch the specific admin widgets.** Contextual links and the toolbar are the two that break most visibly; real themes ship a small CSS file for them (a `::before` reset for `.contextual-region .contextual.open ul.contextual-links > li`, and offsets for `body.toolbar-fixed` / `body.toolbar-tray-open.toolbar-horizontal.toolbar-fixed`).

**4. Use a separate admin theme.** Claro or Gin for admin routes is the normal answer and removes most of the problem. Check it is actually configured: `/admin/appearance` and the "Use the administration theme when editing or creating content" setting.

## CKEditor 5 is the sharpest trap

CKEditor 5 is **not** in an iframe, so a stylesheet declared for the editor is applied to the whole page:

> "stylesheets applied via `ckeditor5-stylesheets` are loaded like any other CSS file but are applied to the entire page instead of on the `<iframe>`."
> <https://www.drupal.org/docs/core-modules-and-themes/core-modules/ckeditor-5-module/how-to-style-custom-content-in-ckeditor-5>

Core's documented remedy is to scope every rule with `.ck-content`. Pointing `ckeditor5-stylesheets` at the full Tailwind bundle leaks the whole utility sheet — and Preflight — into the admin page. The clean pattern is a **second, small build** for the editor:

```yaml
# THEME.info.yml
ckeditor5-stylesheets:
  - dist/css/ckeditor.css
```

built from a CSS entry that contains only what editor content needs (typography, no Preflight, no utilities), with the bundler configured to emit that file separately. Overriding a base theme's `ckeditor5-stylesheets` is still an open core issue ([#3458077](https://www.drupal.org/project/drupal/issues/3458077)), so a sub-theme cannot simply unset the parent's value.

## Cascade layers lose to Drupal's unlayered CSS

This is the failure people actually hit, and it is not the aggregator's fault. Layered styles always lose to unlayered styles at the same cascade level, and core's CSS is unlayered:

- [#3587475](https://www.drupal.org/project/ai_rag_search_chat/issues/3587475) (fixed 2026-07-29): "Tailwind v4 `@layer` styles lose specificity to unlayered theme CSS (e.g., Claro normalize resets)". The fix was an **unlayered** compatibility stylesheet.
- Drupal Canvas hit the same class of bug: generated utilities could not beat System module's unlayered `.hidden`, so `md:block` failed; the fix passes utilities unlayered.
- Core does not use cascade layers at all: "CSS layers are currently only supported at the import level … Which we don't use in Drupal currently" (<https://www.drupal.org/project/css_layers>).

Practical rule: when a utility loses to core or base-theme CSS, the answer is an unlayered rule or a prefix + higher specificity, not `!important` sprinkled per case.

## What Drupal's CSS aggregation actually does

Verified on 2026-09-04 by running core 11.x's own regexes (`core/lib/Drupal/Core/Asset/CssOptimizer.php` and `CssCollectionOptimizerLazy.php`) over modern CSS.

| Construct | Result |
|---|---|
| `@layer theme, base, components, utilities;` | survives, whitespace collapsed |
| `@property --x { … }` | survives |
| `oklch()`, `color-mix()`, `calc(100% - var(--gap))` | survive unchanged |
| `@supports`, `@media` | survive |
| `@import "local.css";` | **inlined** by `CssOptimizer::processCss()` |
| `@import "tailwindcss/preflight.css" layer(base);` | **not inlined** — the inlining regex cannot match a `layer()` qualifier — but **hoisted to the top of the aggregate** by `CssCollectionOptimizerLazy::optimizeGroup()`, whose regex ends in `.*;` and does match it |

The non-inlining half is a known core issue: "the CssOptimizer merges imports into a single file while currently ignoring layers … the import will not be inlined" ([#3470829](https://www.drupal.org/project/drupal/issues/3470829), still open).

Consequences for a Tailwind theme:

- **A compiled Tailwind file is safe.** The build inlines every `@import`, so nothing is left for the aggregator to hoist or fail to resolve.
- **Shipping source CSS is not.** A library pointing at `src/tailwind.css` ships a surviving `@import` whose relative path is then resolved against `sites/default/files/css/`, where it 404s. This is exactly what the `vite` module's source-path libraries do in dev; keep aggregation off in that mode.
- Aggregates are **grouped, not merged into one file** (`CssCollectionGrouper` groups by type/media/library/target and preserves relative order), so an `@layer` order declared in one aggregate does not govern another.
- `minified: true` does not bypass the optimizer for a local file — only external assets skip it. Use `preprocess: false` on the library asset when a file must be shipped byte-for-byte.
- Core knows the minifier is fragile ([#3302612](https://www.drupal.org/project/drupal/issues/3302612), open since 2022), with a history of at-rule and whitespace bugs. Test with aggregation **on** before deploying, which is the one check most Tailwind tutorials skip: they tell you to turn aggregation off while theming and never mention turning it back on.

No drupal.org issue reports aggregation breaking Tailwind v4. The behaviour above is read and executed from core source, not from a bug report.

## Stylelint and coding standards

Drupal's CSS standards are SMACSS categories plus BEM-style naming, but they do **not** forbid utility classes: they permit helpers such as `.grid-3`, `.leader`, `.text-center` because "They are meaningful to developers, and highly reusable", and they explicitly give custom themes the most freedom (<https://project.pages.drupalcode.org/coding_standards/css/architecture/>). Core's own stylelint does not enforce BEM either — `selector-class-pattern` is `null` in `core/.stylelintrc.json`.

What does break: `stylelint-config-standard` enables `at-rule-no-unknown`, and stylelint's known-at-keyword list covers `@layer`, `@property`, `@supports` and `@apply` but **not** `@theme`, `@utility`, `@source`, `@variant`, `@custom-variant`, `@plugin`, `@config`, or v3's `@tailwind`. On drupal.org CI, "If the project has its own stylelint configuration file then this will be used. If not, then the appropriate Drupal version of `core/.stylelintrc.json` will be used" (<https://project.pages.drupalcode.org/gitlab_templates/jobs/stylelint/>), so a contrib theme with no config of its own fails on every Tailwind directive.

The contrib answer is a theme-level `stylelint.config.js` that drops `stylelint-config-standard`, adds `stylelint-config-tailwindcss`, and names the at-rules in use:

```js
export default {
  extends: ["stylelint-config-recommended", "stylelint-config-tailwindcss", "stylelint-prettier/recommended"],
  rules: {
    "at-rule-no-deprecated": [true, { ignoreAtRules: ["apply"] }],
    "at-rule-no-unknown": [true, { ignoreAtRules: ["tailwind", "theme", "reference", "source", "plugin"] }],
  },
};
```

plus a `.stylelintignore` covering `node_modules` and `dist/` so generated CSS is never linted. Extend `ignoreAtRules` with whatever the theme actually uses (`utility`, `variant`, `custom-variant`, `config` are missing from the list above). Drupal's own `import-notation: "string"` already matches `@import "tailwindcss";`.

Core is separately considering a first-party utility API ([#3517033](https://www.drupal.org/project/drupal/issues/3517033), Needs review), which cites Tailwind via DaisyUI as prior art — a signal that utility CSS is not against the grain, not a reason to wait for it.
