---
project_env: DSP_LAB_D10
reset_script: "$DRUPAL_SP_ROOT/scripts/lab-seed" "$PWD" legacy_tools >/dev/null; true
runs: 1
tags: [integration, upgrade]
max_turns: 40
timeout_seconds: 1500
---

Find every API in legacy_tools that will break on Drupal 11 and fix it so the module still works on this 10.6 site. Prove it with real tool runs.
