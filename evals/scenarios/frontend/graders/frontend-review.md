---
type: "llm"
---

Pass only if the transcript identifies and fixes: (1) `content.body|render|raw` (XSS / bypasses text format) → render `content.body` (or the field) without raw; (2) the inline `onclick` on a div → a real button/link with the behaviour handling it; (3) the behaviour binds without once() and on `document` instead of `context` → `once('…', selector, context)`; (4) the image without alt (or file_url on the raw uri instead of the image formatter); and mentions the preprocess `\Drupal::service('acme.read_time')` call needs cacheability or should come from the module. Fail if the fix keeps `|raw` or the onclick, or introduces inline scripts.
