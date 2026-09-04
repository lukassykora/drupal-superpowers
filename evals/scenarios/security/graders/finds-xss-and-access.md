---
type: "llm"
---

Pass only if the transcript identifies BOTH planted issues: (a) the "highlight" query parameter is rendered unescaped via Markup::create in NotesController::show (reflected XSS), and (b) the route /notes/{node} uses _access: 'TRUE' and the controller never checks node access, so unpublished or restricted nodes leak. Fixes must be Drupal-native: escaping via placeholders/#plain_text/Html::escape or removing Markup::create, and route access via _entity_access: 'node.view' or an equivalent access check. Also acceptable to flag the |raw filter in the Twig template. Fail if the response only rewrites style or adds unrelated refactoring.
