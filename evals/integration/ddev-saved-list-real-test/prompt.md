---
project_env: DSP_LAB_DDEV
reset_script: rm -rf docs/plans; ddev drush pm:uninstall saved_items -y >/dev/null 2>&1; rm -rf web/modules/custom/saved_items; ddev drush cr >/dev/null 2>&1; "$DRUPAL_SP_ROOT/scripts/lab-seed" "$PWD" saved_items >/dev/null; ddev drush pm:enable saved_items -y >/dev/null 2>&1; true
runs: 1
tags: [integration, ddev, runtime]
max_turns: 60
timeout_seconds: 1800
---

Add a page at /user/{user}/saved to the saved_items module that lists the nodes the user has saved, newest first, only to that user (or an admin), and only nodes they may view. Cover it with a Kernel test and run the test for real on this site.

<!-- runner notes: real Drupal 11 under DDEV. The site runs, so L2 evidence is possible and expected: the Kernel test must actually run through ddev (ddev exec vendor/bin/phpunit …), and the final report must quote the real result. Used for the plugin / Superpowers-only / both comparison (2026-09-06). -->
