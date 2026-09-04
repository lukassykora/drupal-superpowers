# Git handoff

**Git belongs to the user.** The plugin never stages, commits, pushes, merges, rebases, tags, switches branches, or stashes on its own initiative. It ends every task that changed files with a handoff the user can act on in one copy-paste.

## The rule

| Situation | What Claude does |
|---|---|
| Ordinary task that changed files | leaves the working tree dirty and prints the handoff below |
| User says "commit it", "commit and push", "open a PR", "make a branch" | that request is the authorisation for exactly those operations, nothing more |
| User's project has its own git workflow (documented in CLAUDE.md/README) | follow it, still only on request |
| Anything destructive (`reset --hard`, `clean -f`, force push) | never, even on request, without the user running it themselves; the guard hook blocks it |

A request to commit does not authorise a push. A request to push does not authorise a force push, a rebase, or a branch deletion. Ask when the next step is not the one that was named.

## Handoff format (end of the report, after the verification lines)

```
To commit (your call, nothing was staged):

  git add web/modules/custom/saved_items/saved_items.routing.yml \
          web/modules/custom/saved_items/src/Controller/SavedItemsController.php \
          web/modules/custom/saved_items/tests/src/Kernel/SavedItemsRepositoryTest.php

  git commit -m "Add authenticated saved-items endpoint

  Adds /api/saved-items behind the 'use saved items' permission, returns only
  nodes the user may view, with user cache context and per-user cache tag.
  Kernel test covers the access filter."

Not included on purpose: config/sync/*.yml (export with `drush cex` first if you
want the permission change deployed), .idea/ (untracked, unrelated).
```

Rules for the handoff:
- List the exact paths that changed, grouped by module, so the user can drop any of them.
- Suggest a commit message in the project's style: read the last ten messages (`git log --oneline -10`) and match them (Conventional Commits, `Issue #NNN by user:` on drupal.org, plain imperative). Subject in the imperative, English, under ~72 characters.
- Name what you deliberately left out and why (unrelated files, generated artefacts, config that still needs `drush cex`, vendor).
- Mention deploy steps that must accompany the commit (`drush updb`, `drush cim`, `drush cr`, reindex) so the message or the PR body can carry them.
- Say plainly that nothing was staged or committed.

## When the user did ask

- Read `git status --short` and `git diff --stat` first; commit only the paths that belong to the task, never `git add -A` when the tree holds unrelated changes.
- Match the project's message convention; end the message with whatever attribution the user's setup requires.
- Never amend, rebase, or force push unless that exact operation was requested.
- Report the resulting commit hash and branch, and stop; pushing is a separate request.
