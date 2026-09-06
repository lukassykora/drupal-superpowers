---
type: "file_contains"
path: "docs/**/*.md"
pattern: "```php[\\s\\S]*```[\\s\\S]*```(yaml|yml)|```(yaml|yml)[\\s\\S]*```[\\s\\S]*```php"
match: "contains"
min_files: 1
---
