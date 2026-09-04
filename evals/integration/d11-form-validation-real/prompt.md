---
project_env: DSP_LAB_D11
reset_script: D=$( [ -d docroot ] && echo docroot || echo web ); vendor/bin/drush pm:uninstall broken_service greeting_block xss_notes contact_note saved_items -y >/dev/null 2>&1; rm -rf "$D"/modules/custom/broken_service "$D"/modules/custom/greeting_block "$D"/modules/custom/xss_notes "$D"/modules/custom/contact_note "$D"/modules/custom/saved_items; vendor/bin/drush cr >/dev/null 2>&1; "$DRUPAL_SP_ROOT/scripts/lab-seed" "$PWD" >/dev/null; true
runs: 1
tags: [integration, acceptance]
max_turns: 50
timeout_seconds: 1800
---

Add validation to the contact_note form: e-mail must be valid and the note at least 10 characters. Confirm it works.
