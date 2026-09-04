# Evals

See `docs/evals.md` for the format, the runner, and the grading rules. Layout:

    evals/trigger/<skill>/        one case per skill that MUST activate it
    evals/no-trigger/<skill>/     one case per skill that MUST NOT activate it
    evals/scenarios/<name>/       the 14 scenario evals from the brief (§66)
    evals/agents/<name>/          subagent discipline (§68)
    evals/acceptance/<nn>-<name>/ the five acceptance scenarios (§84–88)
    evals/integration/            in-place cases against a real lab named by DSP_LAB_D11 / DSP_LAB_D10 (Stage 8)

Each case is `prompt.md` (frontmatter + prompt) and `graders/*.md`. Fixtures referenced by
`fixture:` live in `../fixtures/`.
