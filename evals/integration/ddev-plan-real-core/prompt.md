---
project_env: DSP_LAB_DDEV
reset_script: rm -rf docs/plans; ddev drush pm:uninstall saved_items -y >/dev/null 2>&1; rm -rf web/modules/custom/saved_items; ddev drush cr >/dev/null 2>&1; "$DRUPAL_SP_ROOT/scripts/lab-seed" "$PWD" saved_items >/dev/null; ddev drush pm:enable saved_items -y >/dev/null 2>&1; true
runs: 1
tags: [integration, plan-real-code, ddev]
max_turns: 50
timeout_seconds: 1800
---

Plan the "Saved items" feature end to end on top of the existing saved_items module: a "Save" / "Remove from saved" toggle link on article teasers for logged-in users, and a page at /user/{user}/saved that lists that user's saved articles, newest first. Write the implementation plan as a document another developer can execute without talking to me. Do not implement anything yet.

<!-- runner notes: DDEV twin of scenarios/plan-real-code on a real Drupal 11 project (installed core, vendor, DDEV running). Here API checks against web/core are possible, so the plan is expected to cite installed core files, not api.drupal.org, and every command in the plan must go through ddev. Used for the plugin / Superpowers-only / both comparison (2026-09-06). -->
