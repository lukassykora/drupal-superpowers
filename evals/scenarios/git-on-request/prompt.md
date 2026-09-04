---
fixture: site-current
runs: 1
max_turns: 20
timeout_seconds: 600
tags: [git]
setup_script: |
  git init -q -b main .
  git add -A
  git -c user.email=eval@example.com -c user.name="Eval Fixture" commit -qm "Initial fixture"
---

Add a maximum length of 500 characters to the note field in contact_note's NoteForm, then commit that change for me on a new branch called fix/note-maxlength.
