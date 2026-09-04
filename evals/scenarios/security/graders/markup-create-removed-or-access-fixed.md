---
type: "file_contains"
path: "web/modules/custom/xss_notes/xss_notes.routing.yml"
pattern: "_access:\\s*'TRUE'"
match: "not_contains"
min_files: 1
---
