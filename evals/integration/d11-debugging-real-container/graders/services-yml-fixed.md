---
type: "file_contains"
path: "web/modules/custom/broken_service/broken_service.services.yml"
pattern: "@entity\\.manager'"
match: "not_contains"
min_files: 1
---
