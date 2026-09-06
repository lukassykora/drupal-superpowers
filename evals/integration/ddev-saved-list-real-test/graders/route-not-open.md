---
type: "file_contains"
path: "web/modules/custom/saved_items/saved_items.routing.yml"
pattern: "_access: *['\"]?TRUE"
match: "not_contains"
min_files: 1
---
