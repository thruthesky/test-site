# Architecture

## Architectural goal

Build a maintainable Laravel-based community site that is easy for both humans and agents to reason about.

## Chosen baseline

- Framework: Laravel
- Rendering: Blade templates
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
- It supports server-rendered pages with targeted interactive enhancement.

## Proposed top-level structure

```text
laravel/
  app/
    Actions/
    Http/
      Controllers/
      Requests/
    Models/
    Policies/
    Services/
  bootstrap/
  config/
  database/
    migrations/
    seeders/
  public/
  resources/
    views/
    js/
  routes/
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

## Application layer rules

Routes:
- group public, auth, member, and admin routes clearly

Controllers:
- accept request
- authorize
- delegate business work
- return response

Form requests:
- centralize validation

Services or actions:
- own non-trivial business workflows
- examples: create post, create comment, update profile, reorder categories

Policies:
- own permission decisions for posts, comments, profiles, and admin functions

Models:
- keep relations, scopes, casts, and lightweight helpers only

Views:
- presentation only
- no hidden business rules

## UI composition

Server-rendered layout:
- one shared application shell
- header with two-level menu
- left sidebar widget area
- center content slot
- right sidebar widget area

Vue CDN usage:
- menu hover enhancement if needed
- image preview for profile upload
- threaded comment reply toggles
- minor progressive enhancement only

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
- serve profile photos from Laravel public storage link or equivalent public path

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
