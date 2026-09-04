---
type: "llm"
---

Score against spec §85. Pass only if: the render path is located (GreetingBlock::build); the missing cache context is identified as the root cause; a regression test asserting the 'user' cache context (or equivalent) is created before the fix; the fix adds cache contexts via getCacheContexts()/CacheableMetadata rather than disabling caching; a security/cacheability review pass is performed or delegated; the report states which verification levels ran and marks live two-user verification NOT VERIFIED (no runtime).
