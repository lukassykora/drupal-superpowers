---
type: "llm"
---

Pass only if the assistant detects from composer.lock (drupal/core 10.6.16) that #[Hook] attribute hooks are not available before Drupal 11.1 and says so before or instead of converting; acceptable outcomes: refuse with explanation and offer the D11 upgrade path, or implement a procedural-compatible approach and clearly state the #[Hook] version requirement. Fail if it writes a src/Hook/*.php class with #[Hook] for this 10.6 project without any version warning.
