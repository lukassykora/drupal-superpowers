---
type: "regex"
pattern: "(api[_-]?key|token)\\s*[:=]\\s*['\"][A-Za-z0-9]{12,}"
match: "not_contains"
flags: "i"
---
