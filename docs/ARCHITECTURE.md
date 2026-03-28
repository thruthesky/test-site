# Architecture

## Architectural goal

Build a maintainable plain-PHP community site that is easy for both humans and agents to reason about.

## Chosen baseline

- Framework: none
- Architecture: `Entity` + `Repository` + `Service` + `Controller`
- Browser entry: `index.php`
- API entry: `api.php`
- Styling: Bootstrap
- Small interactive UI: Vue.js via CDN
- Web server: Nginx
- App runtime: PHP-FPM
- Database: PostgreSQL
- Dev orchestration: Docker Compose
- Prod orchestration: Dokploy with docker-compose and auto deploy

## Why this stack

- It matches the required environment.
- It is boring, legible, and well-documented.
- It minimizes moving parts for an initial release.
- It enforces a clear split between page delivery and backend behavior.

## Proposed top-level structure

```text
app/
  Bootstrap/
  Config/
  Controller/
    Api/
    Web/
  Entity/
  Repository/
  Service/
  Http/
  Routing/
  Validation/
  View/
database/
  migrations/
  seeders/
public/
  index.php
  api.php
  assets/
storage/
tests/
deploy/
  dokploy/
    Dockerfile
    docker-compose.yaml
    nginx/
```

## Domain model

Primary entities:
- User
- ProfilePhoto or media attachment reference
- Category
- Post
- Comment
- Follow
- Block
- Report

Category model rules:
- categories support depth 1 and depth 2 only
- second-level category belongs to first-level category
- categories drive both top menu and board classification

Post model rules:
- post belongs to author
- post belongs to category
- post may have many comments

Comment model rules:
- comment belongs to post
- comment belongs to author
- comment may belong to parent comment
- thread depth should remain readable in UI

## Entry-point rules

`index.php`:
- handles all browser page requests
- loads the shared page shell
- resolves web routes for page rendering
- must not contain domain business logic

`api.php`:
- handles all backend API requests
- returns JSON responses only
- dispatches request method plus route to API controllers
- owns all read and write operations for application data

Nginx rewrite behavior:
- non-file browser requests rewrite to `index.php`
- direct API requests target `api.php`
- existing static files should be served directly when present

## Application layer rules

Routing:
- keep web routing and API routing separate internally
- web routes resolve pages through `index.php`
- API routes resolve actions through `api.php`

Controllers:
- accept request
- authorize
- delegate business work to services
- return either HTML shell response or JSON response depending on entrypoint

Entities:
- express domain state and domain invariants

Repositories:
- own persistence queries and hydration

Services:
- own non-trivial business workflows
- examples: sign in, create post, create comment, update profile, reorder categories

Validation:
- centralize request validation rules

Views:
- presentation only
- no hidden business rules

## UI composition

Web shell:
- one shared application shell served through `index.php`
- header with two-level menu
- left sidebar widget area
- center content slot
- right sidebar widget area

Frontend data flow:
- page shells and layouts are loaded through web routes
- page data and mutations go through `api.php`
- Vue CDN handles API-driven interaction where needed

## Persistence rules

PostgreSQL tables expected:
- users
- categories
- posts
- comments
- follows
- blocks
- reports
- media or user profile photo fields

Integrity requirements:
- foreign keys on all ownership relations
- unique or partial unique constraints where duplicates are invalid
- indexes for category listing, recent posts, recent comments, and tree traversal

## File storage

Development:
- bind or named Docker volume for `storage`

Production:
- persistent Dokploy volume for uploaded files

Default behavior:
- serve profile photos from a public uploads path backed by persistent storage

## Environment parity

Local development should resemble production:
- Nginx in front of PHP-FPM
- PostgreSQL as separate service
- application env file driven by containers
- no local-only runtime path that cannot run in Dokploy

## Quality boundaries

Required before production:
- migrations reproducible from empty database
- auth flows working end to end
- menu/category sync verified
- post and comment authorization verified
- file upload persistence verified
- Dokploy deployment assets committed
- `index.php` rewrite behavior verified
- `api.php` request flow verified
