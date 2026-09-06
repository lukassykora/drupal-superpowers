---
type: "file_contains"
path: "web/themes/custom/tw/tw.libraries.yml"
pattern: "\\n\\s+src/tailwind\\.css:\\s*\\{"
match: "not_contains"
min_files: 1
---
