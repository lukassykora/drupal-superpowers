# Drupal Superpowers for Claude Code — zadání projektu

Toto je původní zadání (brief) projektu, doplněné o upřesnění v části „Doplňky k zadání“ na konci.
CLAUDE.md na něj odkazuje; nekopíruj ho do CLAUDE.md, ani do SKILL.md souborů.

Významy klíčových slov: **MUST** = povinné, **SHOULD** = doporučené (vynechat jen z dobrého důvodu), **MAY** = volitelné.

## 1. Cíl projektu

Open-source Claude Code plugin s pracovním názvem **Drupal Superpowers**.

Cílem není další sbírka Drupal promptů ani generátor boilerplate, ale kompletní agentic development framework pro profesionální Drupal vývoj, fungující podobně jako Superpowers, ale hluboce rozumějící Drupalu.

Plugin má Claude Code vést celým životním cyklem Drupal úlohy:

understand → investigate → research → brainstorm → design → plan → implement → test → inspect runtime → verify in real Drupal → review → prove completion

Výsledné chování odpovídá kombinaci: senior Drupal developer, Drupal solution architect, security reviewer, test engineer, performance specialist, upgrade/migration specialist, DevOps-aware developer, Drupal core/contrib reviewer. Claude nesmí pouze „vědět něco o Drupalu“; musí pracovat jako zkušený Drupal tým.

## 2. Základní principy

- **Evidence before assumptions.** Claude MUST nejprve zjistit skutečný stav projektu. Nesmí hádat: Drupal verzi, PHP verzi, strukturu projektu, dostupné služby, existenci API/hooku, způsob spuštění projektu, test command, docroot, config directory, container name, Drush command, dostupnost MCP.
- **Investigate before editing.** Před editací MUST otevřít existující implementaci a související soubory. Nikdy neupravovat jen podle názvu souboru.
- **Drupal-native before generic PHP.** Preferovat podle situace service, plugin, event subscriber, hook, Entity API, Config API, Cache API, Render API, Queue API, Batch API, Access API před vlastním frameworkem.
- **Core/contrib before custom.** Před netriviální custom funkcionalitou SHOULD zjistit, zda ji neposkytuje core nebo vhodný contrib. Nesmí to způsobovat zbytečnou rešerši u triviálních změn.
- **Version-aware at all times.** Znalosti MUST být aplikovány podle skutečné verze projektu (nepoužít Drupal 11 API v Drupal 10 projektu).
- **Security by default.** Security je součást návrhu i implementace, ne krok na konci.
- **Cacheability is correctness.** Chybná cacheability je correctness bug, ne jen performance problém. Posuzovat při render outputu, permissions, entities, personalized/dynamických datech.
- **Tests are evidence.** Bugfix SHOULD nejprve reprodukovat chybu testem. Nikdy neupravovat test tak, aby jen „prošel“.
- **Runtime evidence beats reasoning.** Pokud lze změnu spustit a ověřit, spustit a ověřit. „Looks correct“ není verifikace.
- **Never claim unverified success.** Neověřené = `NOT VERIFIED`, nikoli „should work / probably works / looks fine“.

## 3. Nejdříve analyzuj existující ecosystem

Samostatná research fáze před implementací. Prostudovat: Superpowers pro Claude Code, grasmash/drupal-claude-skills, drupaltools/skills, Drupal AI Best Practices, Drupal AI Coding Tools documentation, existující Drupal Agent Skills, Drupal MCP Server, MCP Tools, Drush MCP Bridge, Project Context Connector, Drush Webmaster, další aktivně udržované Drupal Claude Code projekty.

U každého: architektura, licence, aktivita, vhodné skills/agents/workflows, problematická místa, co lze znovu použít, co má Drupal Superpowers řešit jinak. Reuse > duplication, ale nekopírovat bez kontroly licence.

Výstup: `docs/ecosystem-analysis.md` (projects evaluated, reusable ideas, things to integrate, things not to copy, architectural decisions).

## 4. Vztah k Superpowers

Plugin MUST fungovat (A) samostatně i (B) společně se Superpowers. Pokud je Superpowers dostupné, SHOULD využít jeho obecné workflows (brainstorming, systematic debugging, TDD, writing plans, executing plans, code review, verification before completion) a přidat Drupal-specific intelligence. Nesmí vzniknout konflikt dvou orchestration systémů. Bez Superpowers musí existovat lightweight fallback pro brainstorming, debugging, planning, testing, verification. Žádná hard dependency na Superpowers.

## 5. Plugin architecture

Dodržet aktuální oficiální Claude Code plugin specification. Navrhovaná struktura:

```
drupal-superpowers/
├── .claude-plugin/plugin.json
├── skills/            (drupal, architecture, testing, debugging, security, upgrade, migration, frontend, performance, runtime)
├── agents/            (tech-lead, researcher, security-reviewer, test-engineer, upgrade-specialist,
│                       legacy-archaeologist, frontend-specialist, performance-reviewer)
├── hooks/hooks.json
├── references/        (versions, security, testing, APIs, patterns)
├── scripts/
├── evals/
├── fixtures/
├── .mcp.json
├── .lsp.json
├── README.md, CONTRIBUTING.md, LICENSE
```

Strukturu upravit, pokud aktuální specifikace doporučuje lepší variantu.

## 6. Minimal context architecture

Plugin nesmí zaplnit context window dokumentací. Každý SKILL.md obsahuje především: účel, trigger conditions, workflow, decision rules, odkazy na supporting references. Detailní znalosti do supporting files (`skills/<skill>/references/*.md`, `scripts/`). SKILL.md SHOULD být výrazně kratší než 500 řádků.

## 7. Skills jsou capability-based

Ne stovky mikroskills (create-controller, create-form…). Preferované skills:

drupal-project-understanding, drupal-research, drupal-architecture, drupal-module-development, drupal-testing, drupal-debugging, drupal-security, drupal-cacheability, drupal-config, drupal-upgrade, drupal-migrate-api, drupal-frontend, drupal-performance, drupal-runtime-verification, drupal-contrib-research, drupal-code-review.

Descriptions musí být přesné, aby nedocházelo k falešné aktivaci.

## 8. Nepřehánět počet agentů

Agent vzniká jen pokud prospívá: isolated context, parallel work, independent review, velký output, jiný tool permission profile, samostatný odborný pohled.

Preferovaní agenti: **drupal-tech-lead** (architektura, orchestrace), **drupal-researcher** (read-only: API, core implementations, change records, contrib, docs), **drupal-test-engineer** (izoluje PHPUnit output), **drupal-security-reviewer** (read-only, nezávislý review), **drupal-upgrade-specialist**, **drupal-legacy-archaeologist** (D7/8/9, technický dluh), **drupal-frontend-specialist** (Twig, SDC, JS, CSS, a11y), **drupal-performance-reviewer**. Nepoužívat subagenty na triviální operace.

## 9. Drupal project onboarding

Skill `drupal-project-understanding` vytvoří **Project Capability Profile**: Drupal version, Drupal CMS vs core distribution, PHP/Composer/Drush/Symfony version, docroot, Composer root, custom module/theme paths, profiles, recipes, config directories, config split, patches, contrib/custom modules, custom themes, database type, multisite, multilingual, content moderation, workspaces, search stack, queue usage, cron, frontend tooling, CI, test tooling, static analysis, coding standards, local runtime, deployment tooling, MCP availability, browser testing availability.

Nevytvářet permanentní projektové soubory bez důvodu; profil držet v session/cache, pokud Claude Code poskytuje mechanismus.

## 10. Drupal Version Router

Ne oddělené agenty pro D10/D11/D12, ale **Version Router + version-specific capability/reference packs**. Verze primárně z composer.lock, composer.json, Drupal::VERSION/runtime. Router rozlišuje: current supported (moderní workflow), previous supported (jen dostupná API), end-of-life (legacy mode + upozornění na EOL rizika), development/preview (jen pokud ji projekt skutečně používá).

## 11. Version-specific knowledge nesmí rychle zastarat

Ne statické seznamy „Drupal 11 does X“. Preferovat dynamické ověření: lokální source code nainstalovaného core, deprecations, Drupal API pro danou branch, change records. Lokální source konkrétní verze má velmi vysokou autoritu.

## 12. Source-of-truth hierarchy

1. Source code konkrétní nainstalované verze
2. Drupal AI Best Practices
3. api.drupal.org pro odpovídající verzi
4. Drupal change records
5. Oficiální Drupal developer documentation
6. Drupal Coding Standards
7. Drupal security documentation
8. Dokumentace contrib projektu
9. Source code contrib projektu
10. Drupal.org issues / GitLab
11. Trusted community material
12. Obecný web

Blog post nikdy není autoritativnější než aktuální core.

## 13. „Show me how core does it“

Pokud si nejsi jistý správným patternem, najdi ekvivalentní implementaci v Drupal core (ConfigEntity, service injection, route test, cache metadata…). Základní research mechanismus pluginu.

## 14. Hlavní feature workflow (netriviální feature)

1. **Orient** – struktura, verze, relevantní kód, konvence, testy, runtime capabilities
2. **Understand** – co systém dělá, execution path, požadovaná změna
3. **Research** – API, core pattern, contrib options, version differences, change records
4. **Brainstorm** – porovnat varianty (service vs plugin, hook vs event subscriber, config vs content entity, state vs config, queue vs sync, core vs contrib vs custom). Ne pro třířádkový bugfix.
5. **Drupal design review** – security, access, cacheability, configuration, translations, revisions, multilingual, deployment, BC, testability, performance (jen relevantní)
6. **Test strategy**
7. **Implement** – nejmenší správné řešení
8. **Static verification**
9. **Automated tests**
10. **Runtime verification**
11. **Independent review** (security/code review subagent u netriviálních změn)
12. **Completion gate**

## 15. Prevent overengineering

Do not refactor unrelated code. Do not introduce abstractions for hypothetical future needs. Do not create helpers used once unless they materially improve correctness. Do not add configuration that was not requested. Do not replace working project conventions with your preferred architecture without reason.

## 16. Drupal architecture knowledge

Minimálně: extension system (modules, themes, profiles, recipes); services (container, DI, autowiring, decorators, service subscribers); Plugin API (discovery, managers, attributes, annotations u starších verzí, derivatives, contexts); hooks (procedural, OOP podle verze, alter); events (Symfony/Drupal, subscribers); routing (routes, parameters, access checks, route subscribers); Form API (validation, submission, AJAX, multistep, FormBase/ConfigFormBase); Entity API (content/config entities, storage, queries, access, fields, bundles, revisions, translations, validation); Render API (render arrays, lazy builders, placeholders, bubbleable metadata); Cache API (tags, contexts, max-age, invalidation, Dynamic Page Cache, Internal Page Cache, render cache, BigPipe); Configuration (config, schema, install, optional, overrides, config split, import/export); State/TempStore/KeyValue; Queue/Cron/Batch; Typed Data; File/Media API; Logging; Mail; JSON/REST; Serialization; Migrate API; Update API (hook_update_N, post updates, deployment); Drush; Multilingual; Content moderation; Workspaces.

## 17. Coding standards

MUST respektovat Drupal Coding Standards; projektový standard doplňuje, nenahrazuje. Podle dostupnosti: PHP_CodeSniffer, Drupal Coder, DrupalPractice, PHPStan, phpstan-drupal, deprecation rules. Neinstalovat nový quality stack, pokud projekt už jeden má.

## 18. Dependency injection

Preferovat DI; `\Drupal::service()` / `\Drupal::entityTypeManager()` nesmí být default v injectable classes. Ale ne dogmaticky – v procedural kontextu (hooky, .module) je globální accessor legitimní podle Drupal conventions.

## 19. Security skill

`drupal-security` + read-only `drupal-security-reviewer`. Kontrolovat minimálně: authentication, authorization, permissions, route/entity/field access, CSRF, XSS, Twig escaping, unsafe `|raw`, Markup, render arrays, SQL injection, unsafe DB queries, SSRF, path traversal, file uploads, private files, command execution, deserialization, redirects, information disclosure, secrets, logging sensitive info, API access, AJAX callbacks, cache leaks, trusted callbacks.

Reviewer rozlišuje: confirmed vulnerability / probable issue / defense-in-depth recommendation / false positive.

## 20. Access-aware development

U práce s content entities/user data vždy otázka „Who is allowed to see/change this?“. Rozpoznat `$storage->load()`, `loadMultiple()`, entity query a zkontrolovat access při následném použití.

## 21. Cacheability skill

`drupal-cacheability`: cache tags, contexts, max-age, BubbleableMetadata, CacheableMetadata, render/page/Dynamic Page Cache, lazy builders, placeholders, BigPipe, entity cacheability. Kontrolovat personalized output, permissions, roles, language, URL/query parameters, entity-derived a config-derived output.

## 22. Drupal testing strategy

Nativní Drupal test framework: UnitTestCase, KernelTestBase, BrowserTestBase, WebDriverTestBase/FunctionalJavascript, Nightwatch. Project-specific: Behat, Cypress, Playwright, DTT ExistingSite – jen pokud je projekt skutečně používá.

## 23. Test pyramid není rigidní

Nejlevnější vrstva, která prokáže chování (Unit → Kernel → Functional → FunctionalJavascript). Ale ne Unit test s desítkami mocků, když Kernel test přirozeně otestuje skutečné chování.

## 24. Bugfix workflow

reproduce failure → understand execution path → write regression test → verify RED → minimum fix → verify GREEN → run neighboring tests → runtime verification where useful. Pokud regression test není prakticky možný, explicitně vysvětlit proč.

## 25. Test integrity

Nikdy: mazat assertions, měnit očekávané hodnoty podle implementace, skipovat bez důvodu, mockovat testované chování, považovat test s nulou assertions za evidence.

## 26. Static quality pipeline

Detekovat project-specific commands (composer scripts, Makefile, Taskfile, package.json, phpcs.xml, phpstan.neon, eslint/stylelint config, CI pipelines). Project commands mají prioritu. Typický gate: composer validate, PHPCS Drupal + DrupalPractice, PHPStan, deprecated API checks, ESLint, Stylelint, Twig lint, PHPUnit, Composer audit – jen relevantní a dostupné.

## 27. Runtime Environment Adapter

Nevázat na Docker. Abstrakce **Drupal Runtime Adapter** detekuje: DDEV, Lando, Docker Compose, Docker, project-specific wrapper, native PHP/Drush, remote dev environment, no runnable environment.

## 28. Runtime command abstraction

Ne natvrdo `drush cr`; adapter určí `ddev drush cr` / `lando drush cr` / `docker compose exec php vendor/bin/drush cr` / `vendor/bin/drush cr` / project wrapper. Totéž pro composer, php, phpunit, npm, drush.

## 29. DDEV policy

Projekt používá DDEV → použít DDEV. Bez runtime: zjistit doporučený setup projektu, dostupnost Dockeru, DDEV. Bez runtime MAY nabídnout disposable DDEV prostředí, nikdy automaticky. Bez explicitního souhlasu: neinstalovat Docker/DDEV, neměnit host config, nepřepisovat project environment.

## 30. Disposable Drupal Lab

SHOULD umět vytvořit izolované disposable prostředí (vývoj samostatného modulu, reprodukce problému, experiment, upgrade test, browser verification). Fallback DDEV. Nesmí kontaminovat projekt (temp/worktree dir), musí být snadno odstranitelné.

## 31. Tři úrovně verification

- **Level 1 — Static:** syntax, coding standards, static analysis
- **Level 2 — Drupal automated:** Unit/Kernel/Functional, Drush bootstrap, cache rebuild, container compilation
- **Level 3 — Live:** running site, HTTP, real browser, real user workflow, logs

Claude MUST jasně říct, které úrovně skutečně provedl.

## 32.–34. Drupal MCP

Volitelná capability, žádná závislost. **MCP Capability Detection** zjistí tools/resources; nepředpokládat konkrétní implementaci (Drupal MCP Server, MCP Tools, Drush MCP Bridge, Project Context Connector). Use cases: site introspection, modules, entity types, bundles, fields, config, permissions, routes, runtime state, logs, cache ops, Drush, content model. Preferovat read-only. Security: least privilege, read-only first, local first, explicit confirmation for destructive ops; production MCP MUST být citlivé prostředí.

## 35. Browser verification

SHOULD podporovat browser automation (Playwright nebo project framework) pro login, permissions, forms, AJAX, JS, admin UI, content workflows, redirects, error pages, rendering. Sledovat console errors, network failures, HTTP status, Drupal errors. Není náhrada PHPUnit, je to další evidence.

## 36. Runtime logs

Watchdog, PHP logs, webserver logs, container logs, browser console, failed HTTP requests. Volitelné log monitors, ne hlučné background monitory automaticky.

## 37.–38. Systematic debugging

`drupal-debugging`: symptom → reproduce → inspect evidence → trace execution → hypotheses → falsify → root cause → regression test → minimal fix → verification. Nikdy error → random change → cache clear → random change.

Drupal-specific znalost: stale caches, container rebuild, plugin discovery, routing, services.yml, config schema, config drift, permissions, entity access, render cache, Twig cache, libraries, behaviors, once(), AJAX, queues, cron, update hooks, post updates, DB schema, migration map, multilingual, revisions, Composer patches, dependency conflicts, deprecated APIs, Symfony compatibility. `drush cr` není univerzální řešení.

## 39. Contrib research

`drupal-contrib-research`: supported Drupal versions, release status, maintenance, security advisory coverage, usage, critical/compat issues, Composer constraints, dependencies, maintainer activity, superseded/obsolete. Popularita je signál, ne důkaz.

## 40. Configuration management

`drupal-config`: rozlišovat configuration / content / state / temporary data / secrets / environment-specific settings. Rozumět config/install, config/optional, schema, dependencies, overrides, config split, settings.php overrides, import/export. Nikdy neopravovat drift `drush cim -y` bez pochopení.

## 41. Deployment-aware development

Určit dopad: code, Composer, DB updates, post updates, config, cache rebuild, migrations, search indexes, queues. Nevymýšlet univerzální pořadí; zjistit workflow projektu.

## 42.–43. Frontend + accessibility

`drupal-frontend`: Twig, autoescaping, filters, libraries.yml, behaviors, once(), preprocess, theme suggestions, render arrays, SDC, CSS/JS standards, responsive, a11y, cacheability. Preferovat design system projektu. A11y podle rozsahu: semantic HTML, keyboard, focus, labels, ARIA jen kde nutné, contrast, screen reader, Drupal conventions; využít accessibility tree, pokud je browser automation.

## 44. Performance

`drupal-performance`: N+1 loads, entity/DB/Views queries, render pipeline, cache hit/miss, cache metadata, BigPipe, lazy builders, expensive hooks/subscribers, cron, queues, external calls, memory, large loads. Evidence před/po, pokud lze.

## 45.–47. Upgrade & legacy

`drupal-upgrade-specialist`: D7 → modern, 9→10, 10→11, 11→next, minor compat, custom module modernization, contrib major upgrade, PHP/Symfony compat. Workflow: inventory → target version → compatibility matrix → Composer constraints → contrib compat → deprecations → custom code analysis → automated transformations → manual transformations → tests → upgrade execution → runtime verification.

Tooling: Upgrade Status, Drupal Rector, PHPStan, phpstan-drupal, deprecation rules, Composer, change records. Rector output není evidence správnosti – review + testy.

`drupal-legacy-archaeologist`: nepřepisovat hned; map architecture, assumptions, deprecated APIs, unsupported deps, business-critical behavior, missing tests, migration risks.

## 48. Migrate API

Nezaměňovat version upgrade s Migrate API. `drupal-migrate-api`: source/process/destination plugins, dependencies, idMap, high-water, rollback, stubs, files, media, taxonomy, users, content, translations, paragraphs, custom entities, custom process plugins. Před YAML analyzovat source data.

## 49.–50. Module development & scaffolding

`drupal-module-development`: production-quality modul (.info.yml, .services.yml, .routing.yml, .permissions.yml, .links.*, libraries.yml, config/install, config/schema, src/, tests/) – jen potřebné soubory, žádný boilerplate „pro jistotu“. Preferovat project scaffolding/generator; generated code projde stejným review.

## 51.–53. Drush, destructive ops, environment classification

Drush: detekovat, zjistit wrapper, preferovat structured/read-only commands při investigation, využít pro runtime verification. Nikdy automaticky destruktivní commands.

Bez explicitního souhlasu na neověřeném prostředí nikdy: `drush sql-drop`, `drush site:install`, `drush cim -y`, `drush entity:delete`, `DROP TABLE`, `DELETE` bez safe restriction, database restore, `rm -rf project`, `git reset --hard`, `git clean -fd`, unbounded `composer update`. Výjimka: disposable prostředí vytvořené agentem.

Environment classification: DISPOSABLE / LOCAL / DEVELOPMENT / STAGING / UNKNOWN / PRODUCTION. Neznámé = UNKNOWN, chovat se konzervativně.

## 54.–56. Hooks, LSP, context isolation

Claude Code hooks pro deterministické chování: guardrails (nebezpečné příkazy), lightweight validation (targeted lint po editaci PHP), completion support. Ne full PHPUnit/PHPStan po každém Edit; hooks nesmí zpomalovat.

LSP: využít existující PHP LSP, nevytvářet vlastní. Drupal runtime behavior nelze odvodit jen z LSP.

Context isolation: verbose operace (full test suite, log analysis, contrib research, security review, archaeology, upgrade inventory) do subagentů; vracet jen findings, evidence, locations, recommended action.

## 57.–58. Code review

`drupal-code-review`: correctness, Drupal API correctness, version compat, security, access, cacheability, config, performance, tests, standards, maintainability, deployment impact, BC. Priority CRITICAL / HIGH / MEDIUM / LOW / INFORMATIONAL. Styl nepřebíjí funkční bug. U větší změny SHOULD reviewovat jiný agent; úkol reviewera je „find reasons this could fail“.

## 59.–61. Completion gate & evidence report

`drupal-verification`: bez evidence žádné „done“. Report ve formátu PASS / FAIL / NOT VERIFIED — reason / NOT APPLICABLE pro relevantní gates (version detected, architecture inspected, API verified, implementation, coding standards, static analysis, tests, security, access, cacheability, runtime bootstrap, live behavior, browser, regression). Gates jsou relevance-aware (README změna nepotřebuje cacheability review). Na konci stručně: Changed / Verified / Not verified. Ne dlouhý audit, pokud není žádán.

## 62. MCP + runtime precedence

Static structure → filesystem/source; installed runtime state → Drush nebo read-only MCP; user-visible behavior → HTTP/browser. Jedna vrstva nenahrazuje ostatní.

## 63.–65. Knowledge maintenance

Maintenance workflow sledující: Drupal core, supported versions, change records, coding standards, security guidance, AI Best Practices for Drupal, Claude Code plugin/skills spec, Drupal MCP ecosystem. Reference packy verzované, se staleness metadata:

```yaml
verified_against:
  drupal: 11.x
last_reviewed: YYYY-MM-DD
sources:
  - drupal-core
  - api.drupal.org
```

CI SHOULD detekovat podezřele staré reference. Preferovat reuse/reference AI Best Practices for Drupal před vlastní konkurenční verzí – plugin je orchestration + execution vrstva, ne knowledge silo.

## 66.–68. Evaluation suite (povinná)

`evals/` s realistickými scénáři: create module (permission-protected route: structure, DI, access, tests, standards); security (intentional XSS/access bug – najít); cache (personalized output bez cache context – najít); fake API (neexistující metoda – nehalucinovat, ověřit); wrong Drupal version (D10 projekt, D11-only API – rozpoznat); debugging (chybná service definition – reprodukovat, root cause); regression test (nejdřív failing test); upgrade (D10 modul s deprecated API – plan + safe implementation); legacy (D7 modul – nepřepisovat bezhlavě); runtime unavailable (nepředstírat live verification); existing DDEV (použít project DDEV); MCP available (využít pro introspekci); MCP unavailable (zvládnout bez něj); dangerous environment (UNKNOWN/production-like – žádný destructive command).

Skill activation evals: should trigger / should not trigger (drupal-security se neaktivuje při opravě překlepu v README; drupal-upgrade ne při běžném update obsahu). Descriptions optimalizovat podle evalů.

Agent evals: zbytečné spawning, parallelism, duplicitní výzkum, token cost, context pollution.

## 69.–71. Plugin self-tests, fixtures, CI runtime

CI minimálně: validate plugin structure, SKILL.md frontmatter, agent definitions, hooks, MCP config, internal links, scripted unit tests, skill activation evals, representative integration evals. Použít `claude plugin validate --strict`.

Fixtures: Drupal current stable, previous supported, legacy, broken module, security, cacheability, upgrade. Malé, ne obří reálné projekty.

CI může používat DDEV/container; oddělit plugin CI infrastructure od user project runtime.

## 72.–74. Contribution mode, Composer, dependencies

Rozlišovat Drupal site project / custom module / Drupal.org contrib module / Drupal core contribution – jiné test workflow, issue conventions, compat, metadata, no site assumptions.

Composer: core-recommended, core-composer-scaffold, recommended-project, Drupal CMS, patches, repositories, minimum-stability, constraints, conflicts. Nikdy bezdůvodně neuvolňovat constraints. Nová dependency: potřebujeme ji? neposkytuje ji core? maintained? compatible? attack surface? Minimal dependencies.

## 75.–78. Dokumentace, secrets, git, scope

Dokumentaci (README, config, API notes, update/deployment notes) jen když ji feature vyžaduje. Nikdy commitovat secrets, vypisovat credentials, ukládat tokens do .mcp.json. Plugin nikdy automaticky commit/push/force push/merge/rebase. Scope discipline: „Fix this access bug“ ≠ refactor + baseline cleanup + formatting; vedlejší problémy reportovat zvlášť.

## 79.–80. UX

Nezahlcovat procesem; automatic activation je hlavní UX. Explicitní commands mohou existovat: `/drupal-superpowers:audit`, `:debug`, `:upgrade`, `:review`, `:verify`, `:understand-project`. Complexity-sensitive orchestration: malý task (typo, label, CSS) bez ceremonie; komplexní task (entity type, integrace, migrace, upgrade, auth, workflow) plný workflow.

## 81.–82. MVP a Phase 2

**MVP:** project understanding, version router, Drupal research, architecture, module development, testing, debugging, security, cacheability, config management, contrib research, runtime adapter, DDEV adapter, native Drush adapter, optional MCP integration, browser verification, code review, completion gate, upgrade specialist, eval framework.

**Phase 2:** frontend specialist, SDC, performance profiling, Migrate API specialist, D7 migration workflows, core contribution mode, advanced a11y, advanced MCP, architecture reports, CI recommendations.

## 83. Deliverables

Plně validní plugin; README (installation, quick start, architecture, Superpowers interoperability, runtime support, optional DDEV, optional MCP, security model, examples, troubleshooting); `docs/architecture.md`; `docs/ecosystem-analysis.md`; `docs/security.md`; `docs/runtime.md`; `docs/evals.md`; CONTRIBUTING (pravidla pro nové skills/reference packs).

## 84.–88. Acceptance scenarios

1. **„Add an authenticated endpoint returning the current user's saved items.“** – autonomně: detect project/version → read code → understand architecture → verify APIs → core/contrib check → smallest architecture → permissions/access → cacheability → test level → regression/behavior test → verify fail → implement → PHPCS → PHPStan → PHPUnit → bootstrap Drupal → verify endpoint → unauthorized + authorized access → logs → optional browser → independent review → exact evidence → completion.
2. **„This page sometimes shows data for the previous user after logout/login. Fix it.“** – detect version → render path → reproduce → cache metadata → missing context/tags/max-age → core patterns → regression test RED → fix → GREEN → runtime/browser two users → security/cacheability review → evidence. Nikdy náhodné editování.
3. **„Upgrade this custom module from Drupal 10 to Drupal 11.“** – inventory → APIs/deps → target constraints → deprecation analysis → change records → automated/manual → Composer metadata → implement → PHPCS → PHPStan → PHPUnit → install against target → enable → `drush cr` → workflows → remaining incompatibilities. Nikdy jen „run Rector → done“.
4. **„Add validation to this form.“ bez Dockeru** – inspect → version → form → Form API validation → nejlevnější testy → static checks; `Runtime verification: NOT VERIFIED`, nabídnout disposable DDEV; neblokovat práci.
5. **Projekt s Drupal MCP** – detect → read-only introspection → korelace s repem → change přes normální code workflow → MCP/Drush pro safe verification. Repository zůstává source of truth.

## 89.–90. Filozofie

Ne učit Claude tisíce faktů, ale: how to find the right Drupal answer, which answer applies to this version, the right architectural decision, implementation per Drupal standards, proof that it works.

Řetězec rozhodování: PROJECT → VERSION → EVIDENCE → DRUPAL STANDARD → DESIGN → TEST → IMPLEMENTATION → REAL EXECUTION → REVIEW → PROOF.

## 91. Postup implementace

1. Research aktuálního Claude Code plugin API a Drupal AI ecosystemu
2. Ecosystem analysis + architecture proposal
3. Minimální skill/agent taxonomy
4. Návrh eval suite před masivní implementací
5. MVP
6. Spustit evaly
7. Opravit triggering, orchestration, hallucination problémy
8. Skutečné testy na několika Drupal repositories/verzích
9. Dokumentace (installation, MCP, DDEV)
10. Validace aktuálními Claude Code validation tools

Teprve potom rozšiřovat skills. U každé komponenty: *Zvyšuje skutečně schopnost Claude správně vyřešit Drupal úlohu?* Pokud ne, nevytvářet.

---

# Doplňky k zadání (přidáno 2026-09-04)

## 92. Drush jako primární CLI – rozšíření §28 a §51

Původní zadání Drush zmiňuje (§28, §51, §52, Drush MCP Bridge v §3), ale neříká, co konkrétně od Drush chceme. Doplnění:

- **Drush je jediný podporovaný Drupal CLI.** Pro moderní Drupal (10+) existuje pouze site-local Drush (`vendor/bin/drush`, Drush 12/13+). Globální Drush ani Drush Launcher se nepředpokládají; adapter MUST najít site-local binárku přes Composer root.
- **Verze Drush je součást Project Capability Profile** (`drush --version`, `composer show drush/drush`). Sady příkazů se mezi Drush 11/12/13 liší; nepředpokládat příkazy, které daná verze nemá.
- **Read-only introspection commands** (preferované při investigation, ideálně s `--format=json`):
  `drush status`, `drush core:requirements`, `drush pm:list`, `drush config:status` (drift), `drush config:get`, `drush state:get`, `drush role:list`, `drush user:information`, `drush watchdog:show`, `drush router` / `drush route`, `drush entity:list`? (jen pokud existuje v dané verzi – ověřit `drush list`), `drush php:eval` **jen pro čtení**, `drush updatedb:status`, `drush pm:security`, `drush sql:query` **jen SELECT**.
- **Runtime verification commands:** `drush cr`, `drush updatedb`, `drush deploy` (pokud projekt tento workflow používá), `drush config:import` (ne `-y` bez pochopení, viz §40), `drush pm:enable`, `drush user:login` (jednorázový login link pro browser verification), `drush php:script` pro reprodukci.
- **Scaffolding:** `drush generate` (Drush Code Generator) je preferovaný generátor, pokud projekt nemá vlastní. Nahrazuje Drupal Console generátory (viz §93). Vygenerovaný kód prochází stejným review (§50).
- **Destructive commands** viz §52; navíc před jakoukoli povolenou destruktivní operací na ne-disposable prostředí SHOULD agent nabídnout/udělat `drush sql:dump` do scratch dir.
- **Ověření existence příkazu**: `drush list --format=json` nebo `drush help <cmd>` před použitím méně běžného příkazu. Nehalucinovat Drush příkazy stejně jako Drupal API.

## 93. Drupal Console a další tooling – policy

- **Drupal Console je mrtvý projekt** (poslední release 2020, nepodporuje Drupal 9+). Plugin MUST nikdy nedoporučovat jeho instalaci ani používat jeho příkazy (`drupal generate:*`, `drupal debug:*`). Pokud jej legacy projekt (D8) obsahuje, agent to zaznamená do profilu jako technický dluh, ale nepoužívá ho. Ekvivalenty: generátory → `drush generate`; debug/introspection → Drush read-only příkazy, Webprofiler, Devel (`drush devel:*`), Drupal MCP.
- **Devel / Webprofiler**: používat jen pokud jsou v projektu nainstalované (nikdy je automaticky neinstalovat na non-disposable prostředí); typicky užitečné `drush devel:services`, `drush devel:hook`, `drush devel:event`, `drush devel:reinstall`.
- **PHPUnit v Drupalu**: standardní konfigurace je `core/phpunit.xml.dist` (kopie do `phpunit.xml` v docroot nebo Composer rootu), env `SIMPLETEST_BASE_URL`, `SIMPLETEST_DB`, `BROWSERTEST_OUTPUT_DIRECTORY`; Functional testy vyžadují běžící web server a DB. Adapter MUST zjistit, zda projekt má vlastní `phpunit.xml`, `.ddev/` konfiguraci pro testy nebo Composer script, dřív než sestaví příkaz. Vyžaduje `drupal/core-dev` (zjistit v composer.lock).
- **Drupal core kontribuce**: `core/scripts/run-tests.sh`, Nightwatch, core coding standards přes `core/phpcs.xml.dist`; GitLab CI templates. Jen v core contribution mode (§72).
- **Xdebug / profiling**: nepředpokládat; detekovat (`php -m`, `ddev xdebug status`).
- **Composer**: `composer show drupal/core --format=json`, `composer why`, `composer outdated`, `composer audit`, `composer validate`; `drupal/core-dev` pro test tooling; `cweagans/composer-patches`; patch stack je součást profilu.

## 94. Další doplnění

- **Recipes a Drupal CMS**: od Drupal 10.3/11 existují recipes (`recipe.yml`, `drush recipe`/`php core/scripts/drupal recipe`). Drupal CMS je distribuce postavená na recipes; profil musí rozlišit core-only vs Drupal CMS a nepoužívat install profile patterny tam, kde projekt používá recipes.
- **OOP hooky**: od Drupal 11.1 existují `#[Hook]` atributy; do 10.x pouze procedural. Version Router MUST toto rozhodnutí řídit; nepřepisovat procedural hooky na atributy v D10 projektu.
- **Plugin attributes vs annotations**: atributy od 10.2/10.3 pro většinu core plugin typů; contrib plugin typy mohou stále vyžadovat anotace – ověřit v plugin manageru daného typu, ne podle verze core.
- **Nativní evaly Claude Code**: `claude plugin eval` očekává `evals/**/case.yaml` nebo `prompt.md + graders/*.md`, umí mockovat MCP servery (`evals/mocks/`) a spouštět `scaffold_script` pro fixture setup. Eval suite z §66 SHOULD používat tento formát místo vlastního harnessu; vlastní skripty jen pro to, co nativní formát neumí (např. integration evaly proti běžícímu Drupalu).
- **Slash commands z §79** patří do `commands/` (nebo jako user-invocable skills) podle aktuální plugin spec – ověřit `claude plugin init --with skills,agents,hooks,mcp,lsp` scaffold jako referenci pro aktuální strukturu.
- **Lokální reference Superpowers**: nainstalovaná verze je v `~/.claude/plugins/cache/claude-plugins-official/superpowers/<version>/` – studovat její skills/agents strukturu pro ecosystem analysis (§3) a interoperabilitu (§4).
