---
type: "file_contains"
path: "web/modules/custom/*/*.info.yml"
pattern: "(?i)(?<!/)\\b(oblibene|obl\u00edben\u00e9|clanky|\u010dl\u00e1nky|uzivatel)"
match: "not_contains"
min_files: 1
---
