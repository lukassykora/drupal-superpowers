---
project_env: DSP_LAB_D10
reset_script: "${DRUPAL_SP_ROOT:-/Users/lukassykora/Documents/Repositories/drupal-superpowers}/scripts/lab-seed" "$PWD" legacy_tools >/dev/null; true
runs: 1
tags: [integration, wrong-version]
max_turns: 18
timeout_seconds: 600
---

Convert legacy_tools' hook_form_system_site_information_settings_alter() into an OOP hook class using the #[Hook] attribute.
