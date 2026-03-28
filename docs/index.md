# Docs Index

This `docs/` directory is the repository knowledge base and system of record for future implementation.

Design principles from the Harness approach:
- `AGENTS.md` is the table of contents, not the encyclopedia.
- Repository-local markdown is preferred over undocumented chat decisions.
- Plans, architecture, deployment rules, and quality gaps are versioned together.
- The goal is agent legibility, predictable structure, and low-entropy delivery.

Read order:
1. [`../AGENTS.md`](/Users/thruthesky/tmp/test/site/AGENTS.md)
2. [`product-specs/member-board-site.md`](/Users/thruthesky/tmp/test/site/docs/product-specs/member-board-site.md)
3. [`ARCHITECTURE.md`](/Users/thruthesky/tmp/test/site/docs/ARCHITECTURE.md)
4. [`design-docs/core-beliefs.md`](/Users/thruthesky/tmp/test/site/docs/design-docs/core-beliefs.md)
5. [`deployment/dokploy-production.md`](/Users/thruthesky/tmp/test/site/docs/deployment/dokploy-production.md)
6. [`exec-plans/active/initial-build-and-deploy.md`](/Users/thruthesky/tmp/test/site/docs/exec-plans/active/initial-build-and-deploy.md)
7. [`QUALITY_SCORE.md`](/Users/thruthesky/tmp/test/site/docs/QUALITY_SCORE.md)

Directory map:
- `design-docs/`: agent-first principles and enduring design decisions.
- `product-specs/`: user-visible requirements and acceptance criteria.
- `deployment/`: production deployment constraints and runbooks.
- `exec-plans/active/`: active multi-step plans for implementation.
- `exec-plans/completed/`: archived finished plans.

Current verification state:
- Product scope: drafted
- Architecture baseline: drafted
- Initial implementation plan: drafted
- Dokploy production plan: drafted
- Pure PHP codebase bootstrap: not started
- Local Docker environment: not started
- Production deployment: not started

Document maintenance rules:
- Update the product spec when user requirements change.
- Update architecture docs when folders, boundaries, or stack decisions change.
- Update the active execution plan when phase sequencing changes.
- Update the deployment doc before changing Dokploy behavior or infrastructure.
