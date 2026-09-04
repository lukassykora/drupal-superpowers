# Contrib evaluation criteria

| Criterion | Where to look | Pass | Concern |
|---|---|---|---|
| Supported Drupal versions | latest release's `core_version_requirement` (info.yml in the release tarball / repo), release listing per branch | explicit `^<this major>` in a stable release | only dev branch, or `^10 \|\| ^11` claimed without a tagged release for 11 |
| Release status | project releases page | stable `x.y.z` in the last 12 months | alpha/beta only, or last release > 2 years |
| Maintenance / development status | project page flags ("Actively maintained", "Minimally maintained", "Unsupported", "Obsolete") | actively or minimally maintained | unsupported / obsolete / "seeking maintainer" |
| Security advisory coverage | project page shield ("Covered by the security advisory policy") | covered | not covered (dev/alpha/beta never are) |
| Usage | project usage stats (per branch) | usage on this major, trend stable or up | usage only on old majors, sharp decline |
| Issue queue health | open critical/major bugs, response time, "Needs review" backlog | few criticals, recent maintainer comments | criticals open for months, no maintainer activity |
| Compatibility issues | issues tagged with the target core/PHP version; Rector/Upgrade Status results | none open, or fixed in a release | open "D11/PHP 8.4 compatibility" issues |
| Composer constraints | `composer.json` of the module; `composer why-not drupal/<name>` | installs without loosening constraints | requires `composer-drupal-lenient` or forks |
| Dependencies | `dependencies:` and `require` | few, themselves maintained | pulls large or unmaintained trees |
| Maintainers | project page, commit log | ≥ 2 active, or one very active | single inactive maintainer |
| Superseded | project page notice, "Drupal 10/11 readiness" pages | not superseded | "use X instead" notice |
| Scope fit | README, config UI | covers the need with minimal extra surface | needs heavy customization or exposes far more than needed |
| Code quality (spot check) | `src/`, tests directory, coding standards | tests present, DI used, access handled | no tests, `\Drupal::` everywhere, `_access: 'TRUE'` |

## Data sources
- drupal.org REST: `https://www.drupal.org/api-d7/node.json?field_project_machine_name=<name>` (project node: status flags, `field_security_advisory_coverage`, maintenance/development status IDs), `node.json?type=project_release&field_release_project=<nid>` (releases with `field_release_version`, `field_release_category`), `node.json?type=project_issue&field_project=<nid>&field_issue_priority=400` (critical issues).
- Drupal Code Query: `GET https://api.tresbien.tech/v1/project/<name>`, `POST /v1/composer/scan` for readiness.
- Packagist mirror: `composer show drupal/<name> --all` (through the adapter) for available versions and constraints.

## Verdict template
```
| module | version for D<major> | status | security | usage (D<major>) | criticals | verdict |
|---|---|---|---|---|---|---|
| scheduler | 2.2.x stable | actively maintained | covered | ~60k | 0 | recommend |
```
