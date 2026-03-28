# AGENTS.md

This repository follows a Harness-style workflow inspired by OpenAI's "Harness engineering: leveraging Codex in an agent-first world".

Purpose:
- Build a member-management and board website with a repository-first knowledge base.
- Keep `AGENTS.md` short and use `docs/` as the source of truth.
- Favor boring, legible, low-entropy technology and explicit acceptance criteria.

Read order for every task:
1. `docs/index.md`
2. `docs/product-specs/member-board-site.md`
3. `docs/ARCHITECTURE.md`
4. `docs/design-docs/core-beliefs.md`
5. Relevant execution plan in `docs/exec-plans/`
6. Relevant deployment doc in `docs/deployment/`

Operating rules:
- Do not treat this file as the full spec. Follow the linked docs.
- When behavior, naming, or architecture is unclear, update docs before or along with code.
- Keep the stack stable unless a doc explicitly changes it.
- Prefer repository-local knowledge over chat context.
- Keep changes incremental, testable, and easy to review.

Product scope:
- Public homepage with 3-column layout.
- Member system: sign up, sign in, sign out, profile edit, profile photo upload.
- Board system with categories, posts, threaded comments, and post/comment actions.
- Admin menu management for first- and second-level categories.
- Dokploy production deployment with Traefik-generated domain.

Required implementation stack:
- Development: Docker, Nginx, PHP, PostgreSQL, Bootstrap, Vue.js via CDN.
- Backend style: 100% API-driven.
- Web entry: all browser page requests are handled by `index.php`.
- API entry: all backend API requests are handled by `api.php`.
- Production: Dokploy auto deploy from `git push origin main`.

Project conventions:
- Favor plain PHP with `Entity`, `Repository`, `Service`, and `Controller`.
- Keep domain logic behind API endpoints only.
- Let `index.php` provide the web shell and page routing entry.
- Keep frontend JavaScript minimal and page-local.
- Use PostgreSQL-native constraints for integrity where practical.
- Store uploads in a persistent public disk volume.
- Nginx must rewrite browser routes to `index.php`.

Definition of done for implementation tasks:
- Product behavior matches the product spec.
- Architecture rules remain consistent with `docs/ARCHITECTURE.md`.
- Docs are updated when decisions change.
- Local Docker workflow succeeds.
- Dokploy deployment rules remain satisfied.
- Basic validation, authorization, and failure states are covered.

Execution planning:
- Small tasks may proceed directly.
- Larger features should update or create a file in `docs/exec-plans/active/`.
- Completed plans move to `docs/exec-plans/completed/` later.

Documentation maintenance rules:
- `docs/index.md` must remain the top-level table of contents.
- Product requirements live in `docs/product-specs/`.
- Architecture and constraints live in `docs/ARCHITECTURE.md` and `docs/design-docs/`.
- Deployment truth lives in `docs/deployment/`.
- Quality gaps live in `docs/QUALITY_SCORE.md`.

What not to do:
- Do not introduce a frontend build pipeline if Vue CDN is sufficient.
- Do not place business rules in views or in `index.php`.
- Do not bypass `api.php` for backend mutations or reads.
- Do not hide requirements in chat-only decisions.
- Do not deploy from undocumented configuration.

Current status:
- This repository currently contains planning docs only.
- Implementation, container setup, tests, and deployment are not started yet.
