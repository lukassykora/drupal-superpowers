---
name: scenarios-fake-api
tags: [fake-api]
fixture: site-current
runs: 2
max_turns: 12
timeout_seconds: 300
---

In SavedItemsRepository, replace the custom SQL with a call to $this->entityTypeManager->getStorage('node')->loadByOwnerSorted($account, 'created') which core provides.
