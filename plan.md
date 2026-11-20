# Implementation Plan: Secure Digital File Storage System

**Date**: 2026-11-19 | **Spec**: `Drive Storage.txt` / attached pasted specification

## Summary

Build a new, independently branded web-based file management platform for XAMPP, Apache/MySQL or MariaDB, and cPanel. The implementation uses native PHP 8.3+, PDO, MySQL/MariaDB, server-rendered HTML, Bootstrap 5, Font Awesome, and vanilla ES6 JavaScript. The system will be delivered incrementally: establish a secure foundation first, then authentication and administration, core file management, organization features, versions/sharing, and finally hardening and end-to-end validation.

The design deliberately keeps physical storage names separate from user-visible names, routes all authorization through server-side ownership checks, uses soft deletion before permanent cleanup, and keeps public sharing token-based rather than ID-based.

## Technical Context

**Language/Version**: PHP 8.3+, MySQL 8/MariaDB equivalent, HTML5, CSS3, ES6+ JavaScript
**Primary Dependencies**: Bootstrap 5.x CDN, Font Awesome CDN, Google Fonts CDN; no PHP framework or Composer dependency
**Storage**: MySQL/MariaDB with InnoDB and `utf8mb4`; files stored beneath a configured non-executable storage root
**Testing**: PHP syntax checks, SQL migration/install checks, focused integration tests, browser smoke tests, and a security checklist
**Target Platform**: XAMPP localhost, Apache hosting, and cPanel shared hosting
**Performance Goals**: Paginated listings, indexed ownership/status queries, bounded search results, streaming downloads, and no loading of all files into memory
**Constraints**: No public registration, no framework migration, no direct filesystem access from requests, no plaintext passwords, no fake controls, and no authorization based only on client-supplied IDs

## Constitution Check

*GATE: Pass, subject to re-check after implementation review.*

- **Security by default**: Pass. Authentication, role checks, CSRF, output encoding, prepared statements, upload restrictions, path containment, and audit logging are foundational tasks.
- **Least privilege and tenant isolation**: Pass. Every user-owned query and file operation includes authenticated ownership checks; admin access is separate.
- **Operational simplicity**: Pass. Native PHP and PDO avoid framework/runtime requirements that complicate XAMPP and cPanel deployment.
- **Traceability and testability**: Pass. Each normalized requirement maps to plan items and tasks; each phase has explicit acceptance checks.
- **Maintainability**: Pass. Shared includes, services, repositories, validators, and consistent JSON responses prevent duplicated security logic.

## Applied Guidelines

- Native PHP security patterns: centralize session, CSRF, authorization, input validation, output escaping, and error handling in reusable includes.
- PDO data-access pattern: prepared statements only, explicit transactions for multi-record changes, foreign keys and indexes for ownership/status paths.
- Secure upload pattern: validate upload error, size, extension, MIME, and quota; generate random storage names; keep storage outside executable web paths; deny script execution with Apache configuration.
- Server-rendered progressive enhancement: core flows work with normal requests; Fetch API improves star, tag, share, and modal interactions without making authorization client-side.
- Deployment portability: configuration is externalized, installer checks prerequisites and writability, and all required URLs/paths work under a subdirectory.

## Implementation Steps

### Step 1: [Cross-cutting] Project scaffold and deployment-safe configuration
- **Requirements**: REQ-001, REQ-002, REQ-018
- **Design inputs**: Technology, deployment, configuration, and project-structure sections of the specification.
- **Description**: Create the application directories, bootstrap entry points, environment/config loading, error/logging policy, common response helpers, static assets, storage root, and Apache protections. Keep secrets out of source control and make URL bases/subdirectory deployment configurable.

### Step 2: [Cross-cutting] Database schema, installer, and seed configuration
- **Requirements**: REQ-003, REQ-004, REQ-018
- **Design inputs**: Database-design, installation-system, and system-settings sections.
- **Description**: Implement normalized tables for users, folders, files, versions, tags, file-tags, shares, activity logs, and settings. Add foreign keys, indexes, unique constraints, soft-delete fields, timestamps, and an installation wizard that creates the schema, initial administrator, defaults, storage directories, and an installation lock.

### Step 3: [Cross-cutting] Authentication, sessions, authorization, and tenant isolation
- **Requirements**: REQ-005, REQ-006, REQ-007
- **Design inputs**: Authentication, user-isolation, CSRF, XSS, and access-control sections.
- **Description**: Build login/logout/profile/password-change flows, secure session initialization and regeneration, role middleware, CSRF tokens, input validation, output escaping, ownership-scoped repositories, and consistent unauthorized/error responses. No public registration is exposed.

### Step 4: [US1] Administrator operations and governance
- **Requirements**: REQ-008, REQ-009, REQ-010
- **Design inputs**: Admin dashboard, user management, admin file management, audit log, and settings sections.
- **Description**: Implement the protected admin panel for users, quotas, status, password resets, system files, statistics, security events, allowed extensions, upload limits, retention, and other settings. Prevent deletion/deactivation of the only administrator and protect the current administrator from self-lockout.

### Step 5: [US2] User dashboard, folders, and file explorer
- **Requirements**: REQ-011, REQ-012, REQ-013
- **Design inputs**: Dashboard, folder-management, explorer, responsive, empty-state, notification, and accessibility sections.
- **Description**: Implement dashboard cards, quota progress, recent/starred shortcuts, root and nested folders, breadcrumbs, grid/list views, responsive navigation, empty states, toasts, and accessible forms/modals. Enforce duplicate-name, nesting, and circular-move rules.

### Step 6: [US3] Secure upload, storage, download, preview, and file actions
- **Requirements**: REQ-014, REQ-015, REQ-016, REQ-017
- **Design inputs**: File-management, secure-storage, upload-security, download, preview, quota, and file-details sections.
- **Description**: Implement single/multiple and drag-and-drop uploads, progress feedback, quota checks, MIME/extension/size validation, random physical names, secure streaming downloads, supported previews, rename/move/star/delete actions, details views, and audit events. Never expose physical paths or execute uploads.

### Step 7: [US4] Discovery, metadata, trash, and lifecycle management
- **Requirements**: REQ-018, REQ-019, REQ-020
- **Design inputs**: Search, recent, starred, tags, trash, retention, and notification sections.
- **Description**: Add ownership-scoped search and pagination, recent activity views, starring, user-owned tags and filters, soft-deleted files/folders, recursive restore, permanent deletion with dependency cleanup, empty-trash behavior, and configurable retention cleanup.

### Step 8: [US5] Version history and rollback
- **Requirements**: REQ-021
- **Design inputs**: File-version-history and audit-log sections.
- **Description**: Preserve prior physical revisions, create version metadata and counters, list/download versions, restore a previous version safely, and record all version operations. Enforce owner and deletion checks for every version endpoint.

### Step 9: [US6] Secure public sharing
- **Requirements**: REQ-022, REQ-023, REQ-024
- **Design inputs**: Sharing, password-protected sharing, public-share-page, and share-security sections.
- **Description**: Create random-token links for files/folders with active state, optional hashed password, expiration, view counts, password unlock flow, preview/download access, folder listing and optional ZIP generation, and strict token/resource/deletion validation.

### Step 10: [Cross-cutting] Responsive UX, observability, hardening, and documentation
- **Requirements**: REQ-025, REQ-026, REQ-027
- **Design inputs**: Responsive design, accessibility, performance, error handling, README, security checklist, and deployment sections.
- **Description**: Complete mobile/tablet behavior, keyboard/focus/contrast checks, pagination and query tuning, friendly production errors with server-side logs, security headers where compatible, README/XAMPP/cPanel instructions, and administrator-controlled installer lock/removal guidance.

### Step 11: [Cross-cutting] Verification and acceptance testing
- **Requirements**: REQ-028
- **Design inputs**: Testing requirements and final acceptance criteria.
- **Description**: Execute phase gates and end-to-end tests for authentication, ownership isolation, file/folder lifecycle, quotas, sharing, versions, administration, CSRF/XSS/SQL injection/path traversal, upload execution blocking, and deployment prerequisites. Fix failures before release.

## Project Structure

```text
drive-storage/
├── index.php
├── login.php
├── logout.php
├── dashboard.php
├── files.php
├── folders.php
├── upload.php
├── download.php
├── preview.php
├── search.php
├── recent.php
├── starred.php
├── trash.php
├── profile.php
├── share.php
├── public_share.php
├── versions.php
├── tags.php
├── admin/
│   ├── index.php
│   ├── users.php
│   ├── user_create.php
│   ├── user_edit.php
│   ├── files.php
│   ├── logs.php
│   └── settings.php
├── config/
│   ├── config.php
│   └── database.php
├── includes/
│   ├── bootstrap.php
│   ├── auth.php
│   ├── admin_auth.php
│   ├── csrf.php
│   ├── validation.php
│   ├── storage.php
│   ├── functions.php
│   ├── repositories/
│   ├── services/
│   ├── header.php
│   ├── sidebar.php
│   └── footer.php
├── assets/
│   ├── css/style.css
│   └── js/app.js
├── database/schema.sql
├── install/install.php
├── storage/.htaccess
├── tests/
└── README.md
```

## Testing Strategy

- **appType**: Mixed server-rendered HTML plus Fetch API endpoints.
- **Critical user journeys**:
  1. Administrator installs the system, logs in, creates a user, sets a quota, and reviews logs.
  2. User creates nested folders, uploads files, downloads/previews them, renames/moves/stars them, and checks quota usage.
  3. User deletes/restores/permanently deletes files and folders while retention cleanup removes only eligible trash.
  4. User creates a protected, expiring share and a visitor unlocks or is denied access correctly.
  5. User uploads a revision, downloads an older version, and rolls it back.
- **primaryValidationStack**: PHP CLI syntax checks, MySQL/MariaDB integration database, HTTP/browser smoke tests, and focused security tests.
- **fallbackMatrix**:
  - `infra-tier`: MySQL/MariaDB integration instance → local XAMPP database. Prerequisite: configured PHP database extension.
  - `browser-tier`: Playwright/browser smoke tests → manual HTTP/form smoke tests. Prerequisite: browser tooling.
- **Environment requirements**: PHP 8.3+, Apache with rewrite/headers support as available, MySQL/MariaDB, PDO MySQL, fileinfo, JSON, session support, writable storage/log directories, and optional ZIP support for folder downloads.
- **knownGaps**: Without ZIP support, folder archive download is unavailable; without browser tooling, responsive and modal behavior requires manual verification.
- **Test data strategy**: Generate isolated test users and randomized filenames per run; use a dedicated test database/storage root; clean database rows and physical files after each suite.
- **Acceptance criteria**: Every final-acceptance item in the specification passes, no unauthorized cross-user resource is accessible, no dangerous upload executes, and all destructive operations leave consistent database and filesystem state.
- **Validation review expectations**: Reviewers confirm requirement traceability, secure ownership checks, correct HTTP/error behavior, migration/install repeatability, and that no control is a non-functional placeholder.

## Complexity Tracking

No constitution violations are planned. The service/repository split is justified by repeated ownership and transaction-sensitive operations; a framework would conflict with the deployment and architecture constraints.

## Task Breakdown

### Phase 1: Setup

- [ ] T001 [P] [Plan:1.1] Create the PHP project directories and entry-point files under the repository root.
- [ ] T002 [P] [Plan:1.1] Add `config/config.php` for environment, URL base, upload limits, storage root, and safe defaults.
- [ ] T003 [P] [Plan:1.1] Add `config/database.php` and `includes/bootstrap.php` with PDO initialization, exception policy, and shared request bootstrapping.
- [ ] T004 [P] [Plan:1.1] Add `assets/css/style.css`, `assets/js/app.js`, shared layout includes, and CDN asset references.
- [ ] T005 [P] [Plan:1.1] Add `storage/.htaccess` and deployment-safe directory protection without exposing physical paths.

### Phase 2: Foundational

- [ ] T006 [Plan:2.1] Create `database/schema.sql` with normalized tables, foreign keys, indexes, unique constraints, soft-delete fields, and `utf8mb4`.
- [ ] T007 [P] [Plan:2.1] Implement repositories under `includes/repositories/` with prepared statements and ownership-scoped resource queries.
- [ ] T008 [P] [Plan:2.1] Implement `includes/validation.php` for IDs, names, extensions, dates, sizes, and settings.
- [ ] T009 [Plan:2.1] Implement `install/install.php` for prerequisite checks, database creation, default settings, administrator creation, directories, and installation lock.
- [ ] T010 [Plan:2.1] Add installer repeat-use prevention and safe error reporting for failed database or filesystem setup.
- [ ] T011 [Plan:3.1] Implement `includes/auth.php`, secure session startup, `session_regenerate_id(true)`, login/logout, and inactive-account handling.
- [ ] T012 [P] [Plan:3.1] Implement `includes/admin_auth.php` with role checks and current-administrator safety guards.
- [ ] T013 [P] [Plan:3.1] Implement `includes/csrf.php`, request-method checks, JSON response helpers, output escaping, and friendly error handling.
- [ ] T014 [Plan:3.1] Implement `login.php`, `logout.php`, `profile.php`, and password-change handling with password hashing and verification.

### Phase 3: User Story 1 — Administrator operations

- [ ] T015 [US1] [Plan:4.1] Implement admin dashboard metrics and recent activity in `admin/index.php`.
- [ ] T016 [P] [US1] [Plan:4.1] Implement user listing, filtering, creation, editing, activation, deactivation, deletion, password reset, and quota management in `admin/users.php`, `admin/user_create.php`, and `admin/user_edit.php`.
- [ ] T017 [P] [US1] [Plan:4.1] Implement system-wide file inspection and safe administrative actions in `admin/files.php`.
- [ ] T018 [P] [US1] [Plan:4.1] Implement paginated audit-log display in `admin/logs.php`.
- [ ] T019 [P] [US1] [Plan:4.1] Implement validated system settings and extension/upload/retention controls in `admin/settings.php`.
- [ ] T020 [US1] [Plan:4.1] Add audit events for every administrator mutation and verify only administrators can invoke admin endpoints.

### Phase 4: User Story 2 — Dashboard, folders, and explorer

- [ ] T021 [US2] [Plan:5.1] Implement dashboard quota cards, recent files, starred items, quick actions, and empty states in `dashboard.php`.
- [ ] T022 [US2] [Plan:5.1] Implement folder creation, nested navigation, breadcrumbs, rename, delete, and move validation in `folders.php`.
- [ ] T023 [P] [US2] [Plan:5.1] Implement file/folder grid and list views, sorting, pagination, responsive sidebar, and action menus in `files.php`.
- [ ] T024 [P] [US2] [Plan:5.1] Add accessible Bootstrap modals, confirmation dialogs, toasts, loading states, and mobile layouts in `assets/css/style.css` and `assets/js/app.js`.
- [ ] T025 [US2] [Plan:5.1] Add duplicate-name and circular-folder checks with server-side error messages and audit events.

### Phase 5: User Story 3 — File lifecycle

- [ ] T026 [US3] [Plan:6.1] Implement `includes/storage.php` for random physical names, MIME detection, safe path containment, quota calculations, and cleanup helpers.
- [ ] T027 [US3] [Plan:6.1] Implement single/multiple and drag-and-drop upload handling in `upload.php` with progress responses and strict validation.
- [ ] T028 [P] [US3] [Plan:6.1] Implement ownership-checked streaming download and audit logging in `download.php`.
- [ ] T029 [P] [US3] [Plan:6.1] Implement supported preview handling and unsupported-file fallback in `preview.php` without executing uploaded content.
- [ ] T030 [US3] [Plan:6.1] Implement rename, move, star, details, and soft-delete actions with Fetch API enhancement and normal-request fallback.
- [ ] T031 [US3] [Plan:6.1] Add file metadata, quota, upload, download, preview, and mutation audit events.

### Phase 6: User Story 4 — Discovery and trash

- [ ] T032 [P] [US4] [Plan:7.1] Implement ownership-scoped search, pagination, and location/type metadata in `search.php`.
- [ ] T033 [P] [US4] [Plan:7.1] Implement recent and starred views in `recent.php` and `starred.php`.
- [ ] T034 [P] [US4] [Plan:7.1] Implement user-owned tag CRUD, attach/detach operations, and tag filtering in `tags.php`.
- [ ] T035 [US4] [Plan:7.1] Implement trash listing, recursive restore, permanent deletion, empty-trash, and dependency cleanup in `trash.php`.
- [ ] T036 [US4] [Plan:7.1] Implement retention cleanup as a safe admin-triggered or scheduled service with transaction/logging safeguards.

### Phase 7: User Story 5 — Versions

- [ ] T037 [US5] [Plan:8.1] Implement revision upload and preservation of previous physical files in `includes/services/version_service.php`.
- [ ] T038 [P] [US5] [Plan:8.1] Implement version listing and secure version download in `versions.php`.
- [ ] T039 [US5] [Plan:8.1] Implement rollback with atomic metadata/file updates and audit logging.

### Phase 8: User Story 6 — Sharing

- [ ] T040 [US6] [Plan:9.1] Implement random-token share creation, expiration, active state, optional password hashing, and view-count updates in `share.php`.
- [ ] T041 [US6] [Plan:9.1] Implement token-only public resource resolution, password unlock, preview/download, and folder listing in `public_share.php`.
- [ ] T042 [P] [US6] [Plan:9.1] Implement optional ZIP generation with safe archive entry names and bounded resource handling.
- [ ] T043 [US6] [Plan:9.1] Add share disable/delete operations and tests for expired, inactive, wrong-password, deleted-resource, and invalid-token cases.

### Phase 9: Cross-cutting hardening and documentation

- [ ] T044 [P] [Plan:10.1] Complete responsive, keyboard, focus, semantic-label, contrast, and screen-size checks in shared layouts and assets.
- [ ] T045 [P] [Plan:10.1] Add pagination/index review, streaming behavior, bounded queries, and production logging/error pages.
- [ ] T046 [P] [Plan:10.1] Write `README.md` covering XAMPP, cPanel, database setup, configuration, installer use, storage permissions, and deployment security.
- [ ] T047 [Plan:10.1] Review all state-changing routes for CSRF, all output for escaping, all queries for prepared statements, and all resource access for ownership checks.

### Phase 10: Verification

- [ ] T048 [P] [Plan:11.1] Add PHP syntax and configuration smoke checks for all application files.
- [ ] T049 [P] [Plan:11.1] Add authentication, role, session, CSRF, SQL-injection, XSS, path-traversal, and upload-execution security tests.
- [ ] T050 [P] [Plan:11.1] Add file/folder lifecycle, quota, search, tags, trash, retention, version, and rollback integration tests.
- [ ] T051 [P] [Plan:11.1] Add sharing tests for token validity, password protection, expiration, disabled links, folder access, and ZIP behavior.
- [ ] T052 [Plan:11.1] Execute browser smoke tests for desktop/mobile layouts and record failures against the acceptance checklist.
- [ ] T053 [Plan:11.1] Run the complete acceptance checklist and correct all blocking failures before release.

## Requirement Mapping

The source brief did not contain formal `REQ-XXX` identifiers, so the following normalized IDs provide complete traceability for implementation.

| REQ ID | Description | Plan Items | Implementation Evidence |
|--------|-------------|------------|------------------------|
| REQ-001 | Required PHP/MySQL/Bootstrap/vanilla-JS stack and framework restrictions | 1.1 | `config/`, `assets/`, project scaffold |
| REQ-002 | XAMPP, Apache, and cPanel deployment compatibility | 1.1, 10.1 | `config/config.php`, `.htaccess`, `README.md` |
| REQ-003 | Normalized secure database schema | 2.1 | `database/schema.sql`, repositories |
| REQ-004 | Installation wizard and installation lock | 2.1 | `install/install.php` |
| REQ-005 | Authentication, secure sessions, password hashing, login/logout/profile | 3.1 | `includes/auth.php`, `login.php`, `profile.php` |
| REQ-006 | Admin and user role authorization | 3.1, 4.1 | `includes/admin_auth.php`, `admin/` |
| REQ-007 | User-owned resource isolation | 3.1 | ownership-scoped repositories and endpoints |
| REQ-008 | Admin dashboard and user management | 4.1 | `admin/index.php`, `admin/users.php` |
| REQ-009 | Admin file inspection and audit logging | 4.1 | `admin/files.php`, `admin/logs.php` |
| REQ-010 | Validated configurable settings | 4.1 | `admin/settings.php` |
| REQ-011 | User dashboard, quota display, recent/starred shortcuts | 5.1 | `dashboard.php` |
| REQ-012 | Nested folders, breadcrumbs, rename, move, delete, and circularity checks | 5.1 | `folders.php`, folder services |
| REQ-013 | Responsive grid/list explorer and accessible UI | 5.1, 10.1 | `files.php`, shared layouts, assets |
| REQ-014 | Secure upload, validation, random storage names, and quota enforcement | 6.1 | `upload.php`, `includes/storage.php` |
| REQ-015 | Secure download and supported preview | 6.1 | `download.php`, `preview.php` |
| REQ-016 | File actions, details, metadata, and notifications | 6.1 | file actions, modals, toasts |
| REQ-017 | Upload-directory and path security | 1.1, 6.1, 10.1 | `.htaccess`, storage containment, security tests |
| REQ-018 | Search, recent, starred, and pagination | 7.1 | `search.php`, `recent.php`, `starred.php` |
| REQ-019 | User-owned tags and filtering | 7.1 | `tags.php`, tag repositories |
| REQ-020 | Trash, restore, permanent deletion, and retention | 7.1 | `trash.php`, cleanup service |
| REQ-021 | File versions, downloads, and rollback | 8.1 | `versions.php`, version service |
| REQ-022 | Secure share links with random tokens and lifecycle controls | 9.1 | `share.php`, share service |
| REQ-023 | Password-protected and expiring public shares | 9.1 | `public_share.php`, hashed share passwords |
| REQ-024 | Public file/folder preview, download, and optional ZIP | 9.1 | public-share handlers and ZIP service |
| REQ-025 | CSRF, XSS, SQL injection, path traversal, upload, and authorization hardening | 3.1, 6.1, 10.1 | security helpers and tests |
| REQ-026 | Error handling, observability, and performance | 1.1, 10.1 | logging, pagination, streaming, error pages |
| REQ-027 | README, installation, deployment, security, and testing documentation | 10.1 | `README.md`, acceptance checklist |
| REQ-028 | Complete workflow and final acceptance testing | 11.1 | `tests/`, verification reports |

