# Quality Score

This file tracks readiness by domain so future implementation can focus on the highest-risk gaps first.

## Scoring

- 0: not started
- 1: drafted only
- 2: scaffolded
- 3: implemented but lightly verified
- 4: implemented and verified locally
- 5: deployed and verified in production

## Current scores

| Domain | Score | Notes |
|---|---:|---|
| Project documentation | 1 | Initial project docs created |
| Plain PHP bootstrap | 0 | Not started |
| `index.php` web entry | 0 | Not started |
| `api.php` backend entry | 0 | Not started |
| Local Docker runtime | 0 | Not started |
| Authentication | 0 | Not started |
| Profile photo upload | 0 | Not started |
| Category management | 0 | Not started |
| Board posts | 0 | Not started |
| Threaded comments | 0 | Not started |
| Sidebar widgets | 0 | Not started |
| Authorization | 0 | Not started |
| Dokploy deployment assets | 0 | Not started |
| Production deployment | 0 | Not started |

## Highest-risk areas

- deployment from an empty repository
- threaded comment tree behavior
- persistent upload handling across environments
- menu hierarchy coupled to board categories
