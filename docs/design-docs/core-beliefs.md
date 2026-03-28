# Core Beliefs

This repository adopts a Harness-style operating model: humans steer, agents execute, and the repository carries the durable context.

## Beliefs

1. Repository-first knowledge
- If a decision matters during implementation or deployment, it must live in this repository.

2. AGENTS is a map
- `AGENTS.md` stays short.
- Durable truth belongs in `docs/`.

3. Boring technology wins
- Prefer plain PHP, Bootstrap, Vue CDN, Nginx, PHP-FPM, PostgreSQL, Docker, and Dokploy.
- Avoid unnecessary frameworks and client-side build systems for the initial product.

4. API-first backend
- All backend behavior must be exposed through `api.php`.
- Web pages should consume the backend through HTTP APIs, not hidden PHP page logic.

5. Strict boundaries over cleverness
- Entities, repositories, services, controllers, routing, validation, and persistence each have clear roles.
- Business rules should not be duplicated between controllers, views, and entrypoints.

6. Data integrity at the boundary
- Validate request input.
- Enforce authorization before mutation.
- Back critical integrity with database constraints.

7. Menu is the information architecture
- First-level and second-level categories are not decorative.
- They drive both navigation and board categorization.

8. Same behavior in dev and prod
- Local Docker should mirror Dokploy production structure as closely as practical.
- Differences must be documented explicitly.

9. Persistent files and data
- User uploads and database state require persistent volumes in both local and production environments.

10. Incremental execution
- Large work should be executed by plan, phase by phase.
- Each phase should end in a runnable and testable state.

11. Docs change with code
- If implementation reveals a better decision, update docs in the same change set.

12. Optimize for future agent runs
- Names, folders, and abstractions should be explicit and unsurprising.
- Hidden conventions are a defect.
