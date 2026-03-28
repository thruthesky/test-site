# Product Spec: Member Board Site

## Goal

Build a website that combines:
- member management
- hierarchical menu management
- category-linked board posting
- threaded comments
- a homepage with a classic community-site 3-column layout

The first implementation should optimize for correctness, clarity, and deployability over novelty.

## Required stack

Development mode:
- Docker
- Nginx
- PHP
- PostgreSQL
- Bootstrap
- Vue.js via CDN

Production mode:
- Deploy on Dokploy
- Use auto deploy from `git push origin main`
- Use Traefik-generated production domain
- Put Dokploy deployment assets under `deploy/dokploy/`

Runtime entry requirements:
- all browser page requests are handled by `index.php`
- all backend API requests are handled by `api.php`
- backend code is 100% API-driven
- Nginx rewrites non-file browser routes to `index.php`

## Core users

- Guest
- Registered member
- Administrator

## Information architecture

Main navigation:
- top header displays first-level categories
- hover on a first-level category shows second-level categories
- clicking a category opens the category-specific post list

Homepage layout:
- left sidebar: site statistics
- center content: category lists, post lists, reading pages, auth pages, profile pages
- right sidebar: login box, recent posts, recent comments, recent photos

## Functional requirements

### 1. Member account system

Guests can:
- sign up
- sign in

Members can:
- sign out
- edit profile information
- upload profile photo
- access their own profile page

Expected fields:
- email or login identifier
- display name
- password
- profile photo
- self introduction or short bio

Acceptance criteria:
- sign-up validates unique identity fields
- sign-in uses secure password verification
- profile update only allows owner or admin
- uploaded profile photo is rendered on profile and relevant UI surfaces

### 2. Admin menu management

Administrators can:
- create first-level categories
- create second-level categories
- edit sort order
- enable or disable categories

Rules:
- second-level categories belong to one first-level category
- board categories are driven by the same category tree
- hidden or disabled categories should not appear in public navigation

Acceptance criteria:
- menu order is stable and configurable
- homepage header reflects admin changes without code changes
- category data can drive both navigation and board filtering

### 3. Board system

Members with permission can:
- create posts
- edit own posts
- delete own posts

Administrators can:
- moderate posts

Guests can:
- read public lists and public posts if the category allows it

Post list behavior:
- category-specific list page
- pagination
- show title, author, date, view count, comment count

Post read page behavior:
- show post metadata and content
- directly under content show action buttons:
  - comment
  - edit
  - delete
  - report
  - block
  - follow

Acceptance criteria:
- each post belongs to a category
- list filtering by category is correct
- action buttons render according to auth and permission state

### 4. Comment system

Comment area behavior:
- comment input appears below post action buttons
- comments render as a threaded tree with visible indentation
- each comment shows action buttons

Comment actions:
- create
- edit
- delete
- report
- block
- follow
- reply to existing comment

Acceptance criteria:
- parent-child relationships are preserved
- deleted comments follow a defined policy
  - preferred default: soft-delete content but preserve thread shape
- indentation is clear enough to follow the thread

### 5. Sidebar widgets

Left sidebar:
- total member count
- total post count
- total comment count
- optionally per-category stats later

Right sidebar:
- login panel for guests
- user summary for logged-in members
- recent posts
- recent comments
- recent photos

Acceptance criteria:
- sidebar data loads on homepage and relevant site pages
- empty states do not break layout

## Non-functional requirements

- Bootstrap-based responsive layout for desktop and mobile
- accessible navigation and page shell rendering
- CSRF protection on all state-changing requests
- Authorization on all protected actions
- Rate limiting for auth and abuse-prone actions where practical
- File uploads must validate type and size
- PostgreSQL schema must support menu tree, posts, comments, users, and media references
- web pages must fetch application data through `api.php`

## Recommended first implementation decisions

- plain PHP application without Laravel or other heavyweight framework
- layered backend with `Entity`, `Repository`, `Service`, `Controller`
- one browser shell entrypoint at `index.php`
- one backend API entrypoint at `api.php`
- Bootstrap for layout and components
- Vue 3 via CDN for page interactions and API-driven UI rendering
- Threaded comments implemented with adjacency list parent references first
- Profile photos stored on public disk with persistent volume

## Out of scope for first delivery

- social login
- realtime notifications
- private messaging
- advanced search
- multi-language support
- additional REST endpoint splitting beyond the single `api.php` entry contract
