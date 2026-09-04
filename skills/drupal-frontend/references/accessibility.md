# Accessibility checklist (front-end changes)

Scale the depth to the change: a colour tweak needs the contrast row; a new interactive widget needs every row. Drupal core targets WCAG 2.1 AA.

| Row | Check | Drupal-native way |
|---|---|---|
| Semantics | headings in order (one `h1`), lists as `ul/ol`, landmarks (`header`, `nav`, `main`, `footer`), tables with `th`/`scope` | core templates already provide; keep `{{ attributes }}` and region templates |
| Controls | every interactive thing is a `button` or `a href`; form controls have `<label for>` or `#title` | Form API renders labels; `#title_display: invisible` keeps it for screen readers |
| Keyboard | reachable by Tab, operable with Enter/Space, no keyboard traps, logical order, visible focus (`:focus-visible` styles not removed) | Olivero focus styles as reference; never `outline: none` without replacement |
| Images | `alt` describes content; decorative → `alt=""`; icons with text hidden (`visually-hidden` class) | image field formatter uses the field's alt; `{{ content.field_image }}` |
| ARIA | only when native semantics are missing: `aria-expanded` on toggles, `aria-live` for dynamic status, `role` on custom widgets with full keyboard support | `Drupal.announce()` for live announcements; `Drupal.tabbingManager` for modals |
| Contrast | text 4.5:1, large text 3:1, UI components 3:1 | check the theme's tokens; do not lighten disabled states below 3:1 for essential info |
| Motion / timing | no auto-playing animation without control; `prefers-reduced-motion` respected | CSS media query |
| Language | `lang` on the html element (core does), on inline foreign text when needed | |
| Forms | errors announced and linked to fields; required marked in text not only colour | Form API + Inline Form Errors module when enabled |
| Dynamic content (AJAX, behaviours) | focus managed after updates; announcements for results ("5 items loaded") | `Drupal.announce()`, focus the updated container |

## Verification
- Keyboard walk through the changed UI (Tab, Shift+Tab, Enter, Space, Esc).
- Browser accessibility tree (DevTools / the accessibility snapshot of the browser tool) for names, roles, states.
- Automated pass when available: the project's axe/pa11y/Lighthouse setup, or `npx @axe-core/cli <url>` on LOCAL if node exists; record as `VERIFY L3 a11y`.
- Drupal-specific: `visually-hidden` (not `display:none`) for screen-reader-only text; core's `Drupal.behaviors` keep focus when re-rendering; test with the Claro/Olivero conventions in mind.
