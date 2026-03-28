# Execution Plan: Initial Build And Deploy

Status: planned

## Objective

Implement the first complete version of the member-management and board site locally with Docker, then deploy it to Dokploy production using the documented production path.

## Phase 1. Repository bootstrap

Deliverables:
- Laravel app scaffold in `laravel/`
- local Docker setup with Nginx, PHP-FPM, PostgreSQL
- base README update if needed
- committed project structure that matches `docs/ARCHITECTURE.md`

Exit criteria:
- app boots locally through Docker
- homepage route renders

## Phase 2. Authentication and member profile

Deliverables:
- sign up
- sign in
- sign out
- profile edit page
- profile photo upload

Exit criteria:
- full auth flow works locally
- uploaded profile photo persists

## Phase 3. Category and admin menu management

Deliverables:
- category schema
- admin CRUD for first-level and second-level categories
- ordering and visibility controls
- top navigation reflects category tree

Exit criteria:
- admin changes are visible on public navigation
- categories drive board filtering

## Phase 4. Board and comments

Deliverables:
- post CRUD
- category-based post listing
- post read page
- threaded comments with reply support
- post and comment action buttons

Exit criteria:
- post lifecycle works locally
- threaded comments preserve tree structure

## Phase 5. Homepage widgets and layout completion

Deliverables:
- 3-column layout
- left statistics widgets
- right recent content and auth widgets

Exit criteria:
- homepage and key pages render with stable layout on desktop and mobile

## Phase 6. Dokploy deployment assets

Deliverables:
- `laravel/deploy/dokploy/Dockerfile`
- `laravel/deploy/dokploy/docker-compose.yaml`
- Dokploy-ready Nginx config
- documented env variables

Exit criteria:
- production containers can be deployed by Dokploy

## Phase 7. Production deployment and verification

Deliverables:
- Dokploy app configured
- Traefik domain connected
- deployed site verified end to end

Exit criteria:
- public production URL works
- critical flows pass after deployment

## Risks

- empty repository means all scaffolding must be created from scratch
- threaded comments and menu-category coupling require careful schema design
- file upload persistence must be handled correctly in both dev and prod
- Dokploy details may require adaptation during execution

## Documentation update rule

If a phase reveals a better implementation path, update:
- product spec when user-visible behavior changes
- architecture when structure changes
- deployment doc when production assumptions change
