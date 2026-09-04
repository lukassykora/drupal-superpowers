---
name: scenarios-wrong-version
tags: [wrong-version]
fixture: site-previous
runs: 2
max_turns: 12
timeout_seconds: 300
---

Convert legacy_tools' hook_form_system_site_information_settings_alter() into an OOP hook class using the #[Hook] attribute.
