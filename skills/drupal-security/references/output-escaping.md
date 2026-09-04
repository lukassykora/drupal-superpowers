# Output escaping in Drupal

Twig autoescapes `{{ }}`; the render system escapes strings unless they are `MarkupInterface`. Every path that produces `MarkupInterface` from data is an escaping decision to justify.

| Construct | Escapes? | Use when | Never with |
|---|---|---|---|
| `{{ variable }}` in Twig | yes (autoescape) | default | — |
| `{{ variable\|raw }}` | **no** | only for `MarkupInterface` already sanitized upstream, with a comment | user input, field values, query params |
| `'#plain_text' => $string` | yes | any untrusted string as text | — |
| `'#markup' => $string` | partial: `Xss::filterAdmin()` (admin tag allow-list) | trusted-ish HTML from site admins | user input, query params, arbitrary field values |
| `'#markup' => Markup::create($html)` / `Markup::create()` | **no** | HTML you built entirely from escaped parts | anything containing untrusted input |
| `t('Hello @name', ['@name' => $x])` | `@` escapes, `%` escapes + emphasis, `:` escapes as URL attribute value | user-facing strings with variables | `!` placeholders (removed), building HTML |
| `new FormattableMarkup('<a href=":url">@t</a>', [...])` | placeholders escaped as above; the template string is trusted | markup with placeholders when `t()` is wrong (not translatable) | untrusted template strings |
| `Html::escape($s)` | yes | strings going into attributes or non-Twig output | double-escaping already-escaped Markup |
| `Xss::filter($s)` / `Xss::filterAdmin($s)` | allow-list filtering | intentionally allowing limited HTML from users (comments) | as a substitute for escaping |
| `check_markup($text, $format)` / `'#type' => 'processed_text'` | applies the text format's filters | body fields with text formats | formats that allow full HTML for untrusted users |
| `'#type' => 'inline_template'` | Twig autoescape | small templates with variables | `|raw` inside |
| `'#type' => 'link'` / `Link::fromTextAndUrl()` | escapes title | links | building `<a>` strings by hand |
| JS `drupalSettings` | JSON-encoded; the consumer must not `innerHTML` it | passing data to JS | inserting into DOM as HTML |
| `Url::fromUri($userInput)` | validates scheme; external URLs need `TrustedRedirectResponse` | | redirect to user-provided URL |

Rules:
- Never concatenate variables into HTML strings; build render arrays (`#type`, `#theme`, `#plain_text`, `#attributes`).
- Attributes go through `Attribute` objects or `#attributes` arrays, which escape values.
- Stored data is untrusted: escape on output regardless of source.
- When a reviewer sees `Markup::create`, `|raw`, `filterAdmin` on request data, or `#markup` with interpolation, it is at least PROBABLE XSS until the source is shown to be safe.
- Verify what a core helper does in the installed version (`drupal-lookup Xss::filterAdmin`), not from memory.
