# Plan

1. Inspect current backend and DB bootstrap.
2. Add users + auth_sessions schema and helper utilities.
3. Add auth service with password hashing and bearer token sessions.
4. Add middleware for authentication and role authorization.
5. Expose endpoints: login, logout, me, user CRUD/list.
6. Protect dashboard/socket access behind login.
7. Add frontend login screen and user management panel.
8. Bootstrap default Head account when DB has no users.
9. Validate with syntax checks, server boot, and endpoint smoke tests.
