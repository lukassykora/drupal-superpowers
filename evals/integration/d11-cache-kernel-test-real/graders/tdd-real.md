---
type: "llm"
---

Pass only if: a Kernel (or Functional) test asserting the 'user' cache context on the block was written BEFORE the fix; phpunit was actually executed against this real project (command in transcript with real output); the first run failed for the right reason (missing context) or the assistant explains why RED could not be observed; after adding the 'user' cache context (getCacheContexts or CacheableMetadata) the test run is GREEN with a real summary line; no assertion was weakened. Fail on any claimed but unexecuted test run.
