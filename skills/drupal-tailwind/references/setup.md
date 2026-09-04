# Setting Tailwind up in a Drupal theme

## What exists on drupal.org (checked 2026-09-04)

| Project | Type | Version | Core | Tailwind | Note |
|---|---|---|---|---|---|
| [`ui_suite_daisyui`](https://www.drupal.org/project/ui_suite_daisyui) | theme | 5.0.0-alpha6 (2026-02-15) | `^10.3 \|\| ^11` | **v4** (`^4.0.14`) | the most current v4 reference; Vite 6; needs `ui_patterns` + `ui_icons`; DaisyUI is MIT but flagged `gpl-compatible: false` in its libraries file |
| [`tailwindcss`](https://www.drupal.org/project/tailwindcss) (Starter Kit) | theme | 5.0.0 (2026-05-21) | `^8 \|\| ^9 \|\| ^10 \|\| ^11` | **v3** (`^3.4.0`) | `starterkit: true`, standalone CLI; the v4 move is [#3465482](https://www.drupal.org/project/tailwindcss/issues/3465482), open since 2024 |
| [`tailpine`](https://www.drupal.org/project/tailpine) | theme | 2.0.0-rc3 (2026-03-12) | `^10.2 \|\| ^11` | **v4** with a v3 JS config | 60+ SDC components; loads `@config` on v4, so its `safelist:` array is inert (see the trap below) |
| [`tailwind_jit`](https://www.drupal.org/project/tailwind_jit) | module | 1.2.0 (2025-03-10) | `^10.3 \|\| ^11` | v3-era | compiles from the **rendered page HTML** at request time, so it needs no globs; needs the Tailwind binary in `$settings` and Internal Page Cache |
| [`radix`](https://www.drupal.org/project/radix), [`gin`](https://www.drupal.org/project/gin) | theme | — | — | **none** | Bootstrap 5 and Claro-based respectively; neither ships Tailwind |

There is **no official drupal.org documentation for Tailwind** — no theming guide, no Preflight page, no SDC guidance. Read the contrib themes' source instead of blog posts where the two disagree.

## Choosing the build

All three of these are in live Drupal use; pick what the project already runs, not what is fashionable.

| Approach | Command shape | When |
|---|---|---|
| Standalone CLI | `tailwindcss -i src/tailwind.pcss -o dist/tailwind.css --minify` | no other JS build in the theme; the `tailwindcss` starter kit uses this |
| postcss-cli | `postcss ./css/main.css -o ./dist/main.css` with `postcss.config.mjs` = `{ plugins: { "@tailwindcss/postcss": {} } }` | an existing PostCSS chain; `tailpine` uses this |
| Vite | `vite build` with `@tailwindcss/vite` | the theme already bundles JS, or HMR is wanted; `ui_suite_daisyui` uses Vite 6 |

The [`vite` contrib module](https://www.drupal.org/project/vite) wires Vite into Drupal's asset system: set `vite: true` on a library, point the library at the **source** path, and the module rewrites it from `manifest.json` and switches to the dev server automatically. It also supports SDC through `vite.enableInAllComponents: true` plus `libraryOverrides` in `*.component.yml`.

## Wiring the output into Drupal

Compiled CSS is a normal theme asset. Two real examples:

```yaml
# ui_suite_daisyui.libraries.yml — Vite output, theme group
daisyui:
  license:
    name: MIT
    url: https://github.com/saadeghi/daisyui/blob/v5/packages/daisyui/LICENSE
    gpl-compatible: false
  css:
    theme:
      "dist/css/app.css": { minified: true }
```

```yaml
# tailwindcss.libraries.yml — standalone CLI output, base group
global-styling:
  css:
    base:
      dist/tailwind.css: {}
```

Then attach it globally in `THEME.info.yml`:

```yaml
libraries:
  - THEME/global-styling
```

Rules that are specific to Drupal:

- **Point the library at compiled output, never at the source CSS.** The source still contains `@import "tailwindcss" …`; see [drupal-integration.md](drupal-integration.md) for exactly what Drupal's aggregator does with that.
- **`minified: true` is not an aggregation opt-out.** Core only skips the optimizer when an asset is *both* minified *and* external. To keep a file out of the optimizer, set `preprocess: false` on it.
- The CSS group (`base`, `layout`, `component`, `theme`) sets ordering against core's own CSS. `base` is the usual choice for a utility sheet; `theme` puts it last.
- Removing core or base-theme CSS is `libraries-override` / `libraries-extend` in `THEME.info.yml` ([change record](https://www.drupal.org/node/2497313)); core's Olivero is the canonical example. Note that no Tailwind theme on drupal.org actually does this — `tailpine` re-implements ~21 core component stylesheets at `weight: -10` instead.

## Tailwind 4 skeleton for a Drupal theme

```css
/* src/tailwind.css — compiled to dist/tailwind.css */
@import "tailwindcss" source(none);

@source "../templates/**/*.{twig,js}";
@source "../components/**/*.{twig,js,yml}";
@source "../*.theme";
@source "./safelist.txt";

@theme {
  --color-brand: oklch(0.55 0.18 264);
  --font-display: "Inter", sans-serif;
}
```

`@theme` replaces `theme.extend`; `--*: initial` inside `@theme` drops Tailwind's defaults entirely. A prefix is now part of the import: `@import "tailwindcss" prefix(tw)`.

## Migrating a theme from v3 to v4

Treat it as its own task with a class inventory in hand. The three removals that break Drupal themes silently:

> "The `corePlugins`, `safelist`, and `separator` options from the JavaScript-based config are not supported in v4.0."
> <https://tailwindcss.com/docs/upgrade-guide>

- `safelist: [...]` → a safelist file registered with `@source`, or `@source inline("…")`. **A v3 config loaded on v4 via `@config` does not error — the safelist is simply ignored.** `tailpine` ships ~130 safelisted grid classes this way, and on Tailwind 4 they are inert.
- `corePlugins: { preflight: false }` → omit the preflight import (see [drupal-integration.md](drupal-integration.md)).
- `prefix: 'tw-'` → `@import "tailwindcss" prefix(tw)`.
- `content: [...]` → `@source` lines, ideally with `source(none)`.

Verify the migration by diffing the class inventory of the old and new compiled CSS, not by looking at a page:

```
grep -o '^\.[a-zA-Z0-9_:\\.-]*' dist/old.css | sort -u > /tmp/before.txt
grep -o '^\.[a-zA-Z0-9_:\\.-]*' dist/tailwind.css | sort -u > /tmp/after.txt
diff /tmp/before.txt /tmp/after.txt
```
