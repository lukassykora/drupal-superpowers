# Attribution

Drupal Superpowers is MIT-licensed. It reuses **ideas and patterns** from the projects below; where text was adapted, the source is MIT-licensed and named here. No text was copied from GPL-licensed or unlicensed projects; facts learned from them were re-verified against Drupal core or change records and are cited to those primary sources.

| Source | License | What was adapted |
|---|---|---|
| [obra/superpowers](https://github.com/obra/superpowers) (Jesse Vincent) | MIT | Skill structure and description style ("Use when <moment>, before <guarded action>"); the Red-flags "Thought / Reality" table pattern; the verification gate wording (identify → run → read → verify → claim); implementer/reviewer subagent contracts (status vocabulary, "do not trust the report", diff-as-file); the `claude -p --plugin-dir` trigger-test technique; the Skill Priority interop rule that this plugin relies on |
| [grasmash/drupal-claude-skills](https://github.com/grasmash/drupal-claude-skills) (Matthew Grasmick) | MIT | Idea of asking before destructive Drush commands; quality-gate / done-gate reviewer pairing; testing traps (RED-first bugfixes, cheapest bootstrap, anonymous-403 trap, vacuous tests) |
| [ivangrynenko/cursorrules](https://github.com/ivangrynenko/cursorrules) | MIT | OWASP-style organisation of the Drupal security checklist |
| [Bloomidea/drush-mcp](https://github.com/Bloomidea/drush-mcp) | MIT | Tool names used for fingerprinting; confirm-before-destructive guidance mirrored in the MCP capability rules |
| [drupal/ai_best_practices](https://www.drupal.org/project/ai_best_practices) | GPL-2.0-or-later | **Ideas only**: procedural skill style, paired evals per skill, expert-corrections loop concept. Facts were re-derived from core/change records. |
| [drupaltools/skills](https://github.com/drupaltools/skills), [ablerz/claude-skill-drupal-module](https://github.com/ablerz/claude-skill-drupal-module) | GPL-2.0 | **Ideas only**: run-tool → fix loops; monthly change-record freshness check |
| [gxleano/drupal-agentic-workflow](https://github.com/gxleano/drupal-agentic-workflow), [gkastanis/drupal-workflow](https://github.com/gkastanis/drupal-workflow) | none / unclear | **Ideas only**: runtime resolver chain, stack detection, post-write lint hook returning exit 2, hook menu |
| Drupal core, change records, coding standards | GPL-2.0-or-later (core) | Referenced by path and URL; short illustrative snippets in references are original |

If you believe something here needs different attribution, open an issue.
