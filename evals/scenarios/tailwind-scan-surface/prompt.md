---
name: scenarios-tailwind-scan-surface
fixture: site-tailwind
runs: 1
max_turns: 25
timeout_seconds: 900
tags: [tailwind, frontend]
---

Our Tailwind build in the tw theme is not producing half the classes we use, and an editor said the admin toolbar looks broken since we switched to this theme. Review the theme's Tailwind setup, fix what is wrong, and tell me what you changed. There is no npm available here.
