---
name: drupal-tailwind
description: Use when a Drupal theme uses Tailwind CSS: setting it up, wiring the build into the theme and *.libraries.yml, choosing @source globs so Twig, SDC components and preprocess classes are found, safelisting classes Twig builds by concatenation, keeping Preflight out of the admin UI and CKEditor, or migrating a theme from Tailwind 3 to 4.
user-invocable: true
paths:
  - "**/tailwind.config.*"
  - "**/themes/custom/**/package.json"
  - "**/themes/custom/**/*.css"
  - "**/themes/custom/**/*.pcss.css"
---

# Drupal + Tailwind CSS

**Core principle:** Tailwind generates a utility only if it can *see the class name as a literal string in a file it scans*. In Drupal those names live where a default setup never looks: `templates/`, SDC under `components/`, preprocess in `*.theme`, PHP render arrays, and editors' content. Getting the scan surface right is most of this work; the rest is keeping Tailwind's reset out of Drupal's admin UI.

`drupal-frontend` owns everything that is not Tailwind-specific (Twig correctness, escaping, libraries, behaviours, SDC structure, accessibility).

## Orient first

Run `drupal-profile` and read `frontend`: `css_framework`, `css_framework_version`, `css_framework_style` (`css-first(v4)` or `js-config(v3)`), `root` (the directory owning `package.json`, usually the theme), `build`, `package_manager`, `sdc`. Never assume v4: v3 is still maintained as the `v3-lts` dist-tag and many Drupal themes run it. The two versions configure scanning in completely different places.

| | Tailwind 3 (`js-config(v3)`) | Tailwind 4 (`css-first(v4)`) |
|---|---|---|
| Config | `tailwind.config.js` with `content: [...]` | the CSS file: `@import "tailwindcss"` plus `@source` |
| Scan control | `content` globs | auto-detection, `@source`, `source(none)` to switch auto-detection off |
| Safelist | `safelist: [...]` in the JS config | **removed in v4** — use `@source inline("…")` or a plain-text safelist file registered with `@source` |
| Theme values | `theme.extend` in JS | `@theme { --color-brand: oklch(…) }` in CSS |
| Prefix | `prefix: 'tw-'` | `@import "tailwindcss" prefix(tw)` |

## Procedure

1. **Find the scan surface.** List every place a class name can appear in this theme: `templates/`, `components/` (SDC), `*.theme` (preprocess), JS that toggles classes, `*.ui_styles.yml` / other YAML that names classes, and any contrib base theme or contrib module the theme renders through. [references/class-detection.md](references/class-detection.md) has the glob set and the contrib-base-theme pattern.
2. **Write the source globs explicitly.** Prefer `@import "tailwindcss" source(none);` followed by explicit `@source` lines over auto-detection: auto-detection is bounded by `.gitignore` and by the directory the CSS file sits in, which in Drupal is usually wrong in both directions (misses `components/`, walks `node_modules`). In v3, never write `content: ["**/*.twig"]` — it scans `node_modules`.
3. **Handle classes Tailwind cannot see** ([references/class-detection.md](references/class-detection.md)): Twig concatenation (`'badge-' ~ variant`), a computed `addClass()`, classes assigned in PHP, and classes typed by editors. Each needs a lookup map of whole literals, a safelist file, or `@source inline()`.
4. **Wire the build into Drupal** ([references/setup.md](references/setup.md)): compiled CSS goes to a file the theme owns (`css/style.css`), is declared in `THEME.libraries.yml`, and is attached globally via `libraries:` in `THEME.info.yml` or per-component. Never point a library at the **source** CSS: it still contains `@import "tailwindcss" …`, which Drupal's aggregator does not resolve (see below).
5. **Keep Preflight out of Drupal's chrome** ([references/drupal-integration.md](references/drupal-integration.md)): Preflight is a global reset. It reaches the toolbar, contextual links, CKEditor 5's UI and any admin page rendered by the front-end theme. Decide deliberately: a separate admin theme (Claro/Gin) is the normal answer; dropping the Preflight import or scoping it is the answer when the front-end theme also renders admin routes.
6. **Verify** — on top of `drupal-frontend`'s gates: the build ran and the output changed (`VERIFY L1 build`, command plus output size or hash); the classes you rely on are in the compiled CSS (`grep -c 'badge-primary' css/style.css`), which is the only honest check that the scan surface is right; `drush cr` and a page load with aggregation **on**; admin routes and the CKEditor form still render.

## Drupal-specific traps

Each is verified and sourced in [references/drupal-integration.md](references/drupal-integration.md); the short form:

- **A class built by concatenation is a class Tailwind never sees.** Documented in contrib, not theory.
- **v4 auto-detection cannot see core or contrib Twig**, because the standard Composer project gitignores exactly those directories, and **`components/` is usually outside the CSS file's directory** too.
- **Cascade layers lose to Drupal's unlayered CSS.** Core uses no layers, so a layered utility loses to Claro's or System's plain rules.
- **CKEditor 5 is not in an iframe**, so `ckeditor5-stylesheets` pointing at the Tailwind bundle leaks Preflight into the admin page.
- **Aggregation keeps modern CSS but mishandles a layered `@import`** (not inlined, yet hoisted to the top of the aggregate where its path breaks). Compiled output has no `@import` left, which is why a library must point at compiled output only.
- **Editor-authored classes never exist at build time.** Classes typed into a View, a block's class field or CKEditor source are invisible to the scanner; they need a safelist entry or they silently do nothing.

## Decision rules

- New theme, no constraints → Tailwind 4, CSS-first, `source(none)` + explicit `@source`, standalone CLI or Vite depending on what the project already runs.
- Existing v3 theme → do not migrate mid-feature; migrate as its own task with the class inventory in hand, because `safelist`, `corePlugins` and `separator` have no v4 equivalent.
- Front-end theme also serves admin routes → drop Preflight or scope it; do not "fix" the admin UI afterwards with overrides.
- A utility you need is missing from the output → find why the scanner missed it. Adding `!important` or hand-writing the CSS hides a scan-surface bug that will hit the next class too.
- Component styling → utilities in the component's Twig, not a parallel CSS file per component; if a component needs a real class, define it with `@utility` (v4) or `@layer components` rather than scattering `@apply`.
- English everywhere: class helpers, safelist entries, comments, config keys.

## Works with other skills

`drupal-frontend` owns Twig, libraries, behaviours, SDC structure and accessibility; this skill is the CSS-pipeline layer. `drupal-performance` for CSS payload and render-blocking questions. `drupal-runtime-verification` resolves the runtime that runs `npm run build`. The `drupal-frontend-specialist` agent preloads this skill and can do the whole build-and-verify loop in isolation.

## Red flags

| Thought | Reality |
|---|---|
| "The class is in the Twig, it will be generated" | Only if that file is in the scan surface *and* the class is a literal. `'badge-' ~ variant` is not a literal. |
| "I'll just add `@source '**/*'`" | That scans `node_modules` and every contrib module; build time explodes and the output grows. List the directories. |
| "Preflight is fine, Drupal's admin has its own theme" | Only if an admin theme is actually configured and used for admin routes; check `/admin/appearance` and `use_admin_theme`. |
| "Point the library at `src/input.css`, Drupal will handle it" | Drupal's aggregator does not resolve `@import "tailwindcss"`; the page ships a broken import and no utilities. |
| "The utility exists in the browser, so the build is fine" | Dev builds and JIT in watch mode differ from a production build. Verify against the committed/compiled output. |
| "Tailwind 4 is a drop-in upgrade" | `safelist`, `corePlugins` and `separator` were removed; a Drupal theme relying on a safelist breaks silently, not loudly. |
