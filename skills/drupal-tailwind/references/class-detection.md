# Making Tailwind see Drupal's class names

Tailwind generates a utility only when the exact class string appears in a scanned file. Everything below is about widening the scan surface to Drupal's real layout and about the class names that can never be scanned.

## The scan surface in a Drupal theme

| Where classes live | Scanned by default? | Why |
|---|---|---|
| `templates/**/*.html.twig` | only if under the CSS file's directory | Twig markup |
| `components/**/*.twig` (SDC) | usually not | SDC must live in the theme's `components/` directory and may be nested |
| `components/**/*.component.yml` | no | usually holds only prop enum names, with the class strings in the `.twig` lookup map — scan it anyway when a component's schema names classes |
| `THEME.theme` | no | preprocess functions add classes to `$variables['attributes']['class']` |
| `js/**/*.js` | only if under the CSS file's directory | behaviours toggling classes |
| `*.ui_styles.yml`, `*.icons.yml` and similar | no | contrib style plugins name classes in YAML |
| a contrib base theme's `templates/`, `components/` | no | a sub-theme renders the parent's markup |
| PHP render arrays in modules (`src/**/*.php`) | no | `#attributes => ['class' => [...]]` |
| content typed by editors (Views, block class fields, CKEditor source) | **never** | it does not exist at build time |

## Tailwind 4: explicit sources beat auto-detection

Auto-detection is bounded by `.gitignore` and starts from the current working directory. Both defaults are wrong for Drupal: the standard Composer project template gitignores `/web/core/`, `/web/modules/contrib/` and `/web/themes/contrib/` (<https://github.com/drupal-composer/drupal-project/blob/HEAD/.gitignore>), so **every core and contrib Twig template is invisible to auto-detection** — including the parent templates a sub-theme renders through. Turn auto-detection off and list what matters:

```css
@import "tailwindcss" source(none);

@source "../templates/**/*.{twig,js}";
@source "../components/**/*.{twig,js,yml}";
@source "../*.theme";
@source "../js/**/*.js";
@source "./safelist.txt";
```

This is the shape used by the DaisyUI-based Drupal theme `ui_suite_daisyui` (5.0.x, Tailwind ^4.0.14), whose `css/app.pcss.css` uses `source(none)` plus `@source` for `templates/`, `components/` (including `yml`), its `*.ui_styles.yml` and `*.icons.yml`, and a safelist file.
<https://git.drupalcode.org/project/ui_suite_daisyui/-/blob/5.0.x/css/app.pcss.css>

A sub-theme of a contrib Tailwind base theme must reach back into the parent:

```css
@source "../../../contrib/ui_suite_daisyui/templates/**/*.{twig,js}";
@source "../../../contrib/ui_suite_daisyui/components/**/*.{twig,js,yml}";
```
<https://git.drupalcode.org/project/ui_suite_daisyui/-/blob/5.0.x/starterkits/ui_suite_daisyui_starterkit/css/app.pcss.css>

Add `../*.theme` when preprocess functions add classes. That practice is documented for the v3 `content` array too: "We go ahead and include the .theme file to pick up any classes added in a preprocess function."
<https://www.freelock.com/blog/john-locke/2022-11/new-approach-drupal-theming-just-time-drupal-10>

## Tailwind 3: the `content` array

```js
module.exports = {
  content: [
    './templates/**/*.html.twig',
    './components/**/*.twig',
    './js/**/*.js',
    './THEME.theme',
  ],
  safelist: [/* whole class names, or {pattern: /^badge-/} */],
};
```

Never use a bare `content: ["**/*.twig"]`: "Tailwind will even scan node_modules for content which is probably not what you want."
<https://v3.tailwindcss.com/docs/content-configuration>

## The concatenation trap

Twig builds class names at render time, and the scanner reads files, not renders:

```twig
{# Tailwind never sees badge-primary, badge-neutral, … #}
{% set classes = [
  'badge',
  variant and variant != 'default' ? 'badge-' ~ variant,
] %}
```

This is a documented Drupal problem, not a hypothetical. `ui_suite_daisyui` issue #3507203 reports "Twig templates that dynamically generate DaisyUI classes are basically skipped by PostCSS"; #3508146 ("CSS class names are generated programatically in many Twig templates. TailwindCSS processors are unable to identify all class names being used") was fixed by checking in `css/safelist.txt` with the full class names and registering it with `@source "./safelist.txt"`.
<https://www.drupal.org/project/ui_suite_daisyui/issues/3507203> · <https://www.drupal.org/project/ui_suite_daisyui/issues/3508146> · <https://git.drupalcode.org/project/ui_suite_daisyui/-/blob/5.0.x/css/safelist.txt>

Three ways out, in order of preference:

1. **Whole class names in the template.** Map the variant to a complete literal, so the scanner finds it. This is what a well-built SDC does: the `.component.yml` carries `enum: [small, medium, large]` and the Twig carries the full strings.
   ```twig
   {% set variants = {primary: 'badge-primary', neutral: 'badge-neutral'} %}
   {% set classes = ['badge', variants[variant]|default('')] %}
   ```
   Tailwind states the rule directly: "Since Tailwind scans your source files as plain text, it has no way of understanding string concatenation or interpolation." Write `{{ error ? 'text-red-600' : 'text-green-600' }}`, never `text-{{ error ? 'red' : 'green' }}-600`.
   <https://tailwindcss.com/docs/detecting-classes-in-source-files>
2. **A safelist file** listing the class names, registered as a source (`@source "./safelist.txt"` in v4). Best when the variants come from config or content and a template map is impractical. Keep it in version control next to the CSS.
3. **`@source inline("…")`** for a handful of classes, e.g. `@source inline("underline");`. v4 has no `safelist` config key — "The `corePlugins`, `safelist`, and `separator` options from the JavaScript-based config are not supported in v4.0".
   <https://tailwindcss.com/docs/functions-and-directives>

`attributes.addClass('my-class')` and `{{ content.field_tags|add_class('my-class') }}` (Drupal 10.1+) are safe: the literal sits in the scanned Twig. They become unsafe the moment the argument is built rather than written.

Classes stored in the **database** — body text, a block's class field, a View — are the hard case. The established Drupal answer is a safelist file, optionally generated from the database by a Drush command that scans `node__body` / `block_content__body`.
<https://www.oliverdavies.uk/blog/drupal-body-classes-tailwind-css>

## Editor-authored classes

Classes an editor types into a View, a block's CSS-class field, or CKEditor's source view are invisible to the build: "Tailwind won't know of your CMS-authored classes; for eg. adding Tailwind classes to a Drupal View will have no effect!"
<https://www.exemplifi.io/insights/creating-a-robust-tailwindcss-pipeline-for-custom-drupal-theme/>

Decide the policy explicitly: either editors get a fixed vocabulary that is safelisted, or they get named component classes instead of utilities. Do not leave it to chance.

## How to verify the scan surface

Grep the compiled CSS for the classes you rely on. This is the only check that proves the globs are right:

```
grep -c 'badge-primary' css/style.css
grep -o '\.badge-[a-z-]*' css/style.css | sort -u
```

Report the result as a `VERIFY L1` line. A missing class here is a scan-surface bug, never a reason to hand-write the rule.
