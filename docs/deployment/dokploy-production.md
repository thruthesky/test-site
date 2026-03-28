# Dokploy Production Deployment

This document defines the target production deployment contract. It is written now so later implementation and deployment can follow one stable path.

## Production facts

- Dokploy dashboard: `http://209.97.169.136:3000`
- Deployment trigger: `git push origin main`
- Dokploy mode: auto deploy
- Production domain: generated and connected through Traefik
- Deployment assets location: `deploy/dokploy/`
- Compose ID: create directly in Dokploy during setup
- Server SSH target: `ssh root@209.97.169.136`

## Required Dokploy deliverables

The repository must eventually contain:
- `deploy/dokploy/Dockerfile`
- `deploy/dokploy/docker-compose.yaml`
- Nginx config needed by the app
- environment variable template or documented variable list

## Target runtime topology

Recommended production services:
- `app` or `php`: plain PHP PHP-FPM container
- `web`: Nginx container
- `db`: PostgreSQL container, unless Dokploy-managed PostgreSQL is selected later

Required persistence:
- PostgreSQL data volume
- application storage volume for uploads and runtime writable directories that need persistence

## Routing

Traefik requirements:
- public route points to Nginx service
- domain is created in Dokploy and connected through Traefik
- HTTPS should be enabled if Dokploy environment supports it

Nginx requirements:
- browser routes rewrite to `index.php`
- API traffic is served through `api.php`
- static files are served directly when present

## Environment variables expected later

Minimum expected app configuration:
- `APP_NAME`
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- upload and session-related variables as needed by the plain PHP app

## Deployment flow to follow later

1. Implement the plain PHP application and local Docker stack.
2. Add Dokploy deployment files under `deploy/dokploy/`.
3. Push to `main`.
4. Create or connect the Dokploy compose app.
5. Set environment variables and persistent volumes.
6. Generate or attach the Traefik domain.
7. Verify:
   - homepage loads
   - sign up and sign in work
   - profile photo upload persists
   - category navigation works
   - post list and post read pages work
   - comments work
   - browser routes resolve through `index.php`
   - backend requests resolve through `api.php`

## Non-negotiable deployment rules

- Production configuration must be committed, not improvised in chat.
- Local and production container topology should remain close.
- No manual one-off server edits that cannot be reproduced.
- If Dokploy-specific constraints require a change, update this document first.

## Current status

- Documentation only
- No deployment assets created yet
- No Dokploy app created yet
- No production verification performed yet
