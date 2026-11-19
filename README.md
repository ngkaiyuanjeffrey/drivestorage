# Nimbus Vault

Nimbus Vault is a native PHP 8.3+ digital file-storage application designed for XAMPP, Apache/MySQL or MariaDB, and cPanel hosting.

## Current implementation

The secure foundation is implemented: project entry point, configuration, PDO database access, installer, normalized schema, secure sessions, password hashing, CSRF protection, authentication, public standard-user registration, profile password changes, and a responsive dashboard shell.

## Local installation

1. Copy the project to `C:\xampp\htdocs\Drive Storage\`.
2. Start Apache and MySQL in XAMPP.
3. Ensure the configured database is available. The default is `nimbus_vault` on MySQL port `3307`, with MySQL user `root` and an empty local password. Set `NIMBUS_DB_PORT=3306` if your MySQL server uses the standard port.
4. Open `http://localhost/Drive%20Storage/install/install.php`.
5. Create the initial administrator account.
6. Sign in at `http://localhost/Drive%20Storage/login.php`.

Standard users can also register at `http://localhost/Drive%20Storage/register.php`. Registration never creates administrator accounts.

Database settings can be supplied with `NIMBUS_DB_HOST`, `NIMBUS_DB_PORT`, `NIMBUS_DB_NAME`, `NIMBUS_DB_USER`, and `NIMBUS_DB_PASSWORD`.

## Security notes

- Passwords are stored with `password_hash()`.
- State-changing forms require a CSRF token.
- Sessions regenerate their identifier after login.
- Uploaded storage is protected from directory listing and script execution.
- The installer creates `storage/.installed` and cannot be reused until that lock is intentionally removed.

File-management, administration, sharing, versioning, and audit modules are scheduled in the implementation plan.
