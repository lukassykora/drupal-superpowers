# Test integrity rules

A test is evidence only if it would fail when the behaviour is wrong. The following moves destroy that property and are never done to make a run green:

| Move | Why it is forbidden | What to do instead |
|---|---|---|
| Deleting or commenting out an assertion | the test now proves less than it claims | fix the code, or change the expectation with a written reason tied to a spec change |
| Changing an expected value to whatever the implementation returns | encodes the bug | trace why the value differs; the test or the code is wrong, decide which with evidence |
| `markTestSkipped()` (or a `#[RequiresPhpExtension]`-style precondition attribute) without a reason string | hides a failure | skip only for a real environmental precondition, with the reason, and report it as NOT VERIFIED |
| Mocking the service/method under test | tests the mock | move to Kernel level and use the real service |
| Stubbing the container wholesale in a Unit test | tests nothing real | inject dependencies; or Kernel test |
| A test with zero assertions ("it didn't throw") | proves only absence of exceptions | assert the observable result; `expectNotToPerformAssertions()` only for genuine smoke checks and say so |
| Catching the exception the test should surface | silences the failure | let it propagate or `expectException()` explicitly |
| Loosening `assertSame` → `assertEquals` → `assertNotEmpty` | weaker claim | keep the strongest true assertion |
| Increasing timeouts / sleeps to pass a flaky test | hides a race | wait for the condition (`assertSession()->waitForElement`) or fix the cause |
| Setting `$strictConfigSchema = FALSE` | hides missing schema | add the schema |

Reporting rules:
- RED must be for the right reason: quote the failing assertion message; "class not found" is not RED for a behaviour test.
- GREEN is the summary line with counts: `OK (3 tests, 7 assertions)`.
- A test written but not executed is `NOT VERIFIED`.
- Neighbouring tests (the module suite) run after the fix; a new failure elsewhere is a finding, not noise.
