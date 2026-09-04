---
type: "llm"
---

Pass only if ALL hold: (1) the route requires the permission "view reading time" via _permission (not _access: 'TRUE'); (2) the controller obtains services through dependency injection (create()/constructor or autowiring), not \Drupal::service() inside the class; (3) node access is respected (either the route uses the entity parameter converter with access or the controller checks $node->access('view')); (4) a PHPUnit test exists at Kernel or Functional level that covers the permission (403 vs 200 or equivalent); (5) the final message does not claim tests passed unless a test command was actually executed in the transcript.
