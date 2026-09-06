---
type: "file_contains"
path: "docs/**/*.md"
pattern: "\\bTBD\\b|\\bTODO\\b|[Ss]imilar to Task|[Aa]dd appropriate|[Hh]andle edge cases|[Ii]mplement later|[Ff]ill in (the )?details"
match: "not_contains"
min_files: 1
---
