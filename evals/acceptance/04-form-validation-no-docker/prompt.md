---
name: acceptance-04-form-validation-no-docker
tags: [acceptance]
fixture: site-current
runs: 2
max_turns: 20
timeout_seconds: 600
---

Add validation to this form: web/modules/custom/contact_note/src/Form/NoteForm.php — e-mail must be valid and the note at least 10 characters.
