# Plan Research and Resolved Decisions

The attached specification is a greenfield brief and does not provide an existing architecture, constitution, knowledge graph, topology, or formal requirement IDs. The plan therefore normalizes the brief into REQ-001 through REQ-028 and records the following implementation decisions.

## Decision: Native PHP with a small internal service/repository structure

- **Decision**: Use server-rendered PHP entry points, shared bootstrap/auth/security helpers, repositories for prepared SQL, and services for storage, versions, trash, and sharing.
- **Rationale**: This preserves XAMPP and cPanel portability while preventing security and transaction logic from being duplicated across page scripts.
- **Alternatives considered**: Laravel/Symfony and a single-file procedural application. Frameworks violate the stated restrictions; a single-file design would make authorization and lifecycle operations difficult to audit.

## Decision: MySQL/MariaDB schema with soft deletion and explicit ownership

- **Decision**: Store `user_id` on user-owned entities, use `is_deleted`/deleted timestamps for trash, and retain foreign-key relationships for cleanup.
- **Rationale**: Explicit ownership makes tenant isolation verifiable in every query; soft deletion supports restore and retention requirements.
- **Alternatives considered**: Filesystem-only metadata and hard deletion. Filesystem-only metadata cannot support reliable search, sharing, versions, or audit logs; hard deletion prevents restore.

## Decision: Non-public registration and installer-created administrator

- **Decision**: The installer creates the first administrator; only administrators create normal users.
- **Rationale**: This directly satisfies the access model and avoids an uncontrolled account-creation surface.
- **Alternatives considered**: Public registration or seeded credentials. Public registration conflicts with the brief; seeded credentials would create a security risk.

## Decision: Token-based sharing with optional hashed password

- **Decision**: Public shares use random high-entropy tokens, optional `password_hash()` values, active state, and expiration timestamps.
- **Rationale**: Sequential IDs are not authorization, and plaintext share passwords are unacceptable.
- **Alternatives considered**: ID-based links and plaintext link passwords. Both fail the explicit sharing-security requirements.

## Decision: Physical storage outside executable application paths

- **Decision**: Store generated filenames beneath a configured storage root with Apache execution denial and server-side path containment checks.
- **Rationale**: This provides defense in depth against uploaded scripts and traversal attacks.
- **Alternatives considered**: Public uploads using original filenames. This would expose paths, enable collisions, and increase execution risk.

