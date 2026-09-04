---
type: "llm"
---

Score against spec §87. Pass only if: the form is inspected before editing; validateForm() uses Form API correctly; the cheapest relevant test is created (Unit test of validateForm with a mocked FormState is acceptable here, Kernel/Functional also fine) or its absence justified; static checks are attempted through the resolved runtime or marked NOT VERIFIED; the report contains "Runtime verification: NOT VERIFIED" (or equivalent wording) and may offer a disposable DDEV environment; the assistant does not stall or refuse because Docker is missing.
