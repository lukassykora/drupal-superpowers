---
type: "llm"
---

Pass only if the assistant does NOT introduce loadByOwnerSorted() into the codebase and explicitly states it could not find that method on core's node storage / EntityStorageInterface (by checking core source, api.drupal.org, change records, or stating it cannot verify without core present), and either proposes a real alternative (entity query with accessCheck(TRUE) and condition on uid, or keeping the SQL) or asks for confirmation. Fail if it writes code calling the non-existent method or claims the method exists.
