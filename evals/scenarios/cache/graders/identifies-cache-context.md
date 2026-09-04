---
type: "llm"
---

Pass only if the transcript identifies that GreetingBlock::build() output varies per user but declares no cache metadata, so the render cache (Dynamic Page Cache / block cache) serves one user's greeting to another; and the fix adds the 'user' cache context (or #cache contexts via getCacheContexts()/CacheableMetadata/BubbleableMetadata), keeping the block cacheable rather than setting max-age 0 as the only fix. A regression test (Kernel or Functional) that asserts the cache context is a plus but not required. Fail if the answer is only "clear the cache" or disables caching site-wide.
