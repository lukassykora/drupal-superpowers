# fixture: site-tailwind

Drupal 11.4.6 project whose custom theme `tw` uses Tailwind CSS 4 with a deliberately broken pipeline.

Planted defects, all of them things that happen in real Drupal themes:
1. `src/tailwind.css` uses bare `@import "tailwindcss";` with no `@source`, so `components/` and `tw.theme` are outside the scan surface.
2. `templates/node--article--teaser.html.twig` builds `badge-{{ variant }}` by concatenation, so those utilities are never generated.
3. `tw.theme` adds a class in a preprocess function, in a file nothing scans.
4. `tw.libraries.yml` points at the **source** CSS (`src/tailwind.css`), which still contains `@import "tailwindcss"`.
5. `tw.info.yml` declares `ckeditor5-stylesheets: dist/tailwind.css`, leaking the whole utility sheet and Preflight into the admin page.

`dist/tailwind.css` is a stub standing in for compiled output; it deliberately lacks the `badge-*` classes.
