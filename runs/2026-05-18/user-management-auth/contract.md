# Contract - User management 4 roles + login

## 1. Input
- Existing CRM Dashboard repo at `/mnt/f/Code/strongdm-main/CRM-Dashboard/CRM-Dashboard`
- Current stack: Node.js, Express, Socket.io, SQLite, static HTML frontend
- User request: add user management module with 4 roles: Head, CSS Manager, CSS Team Leader, Staff; add login and user provisioning for future role-based expansion

## 2. Output
- Database schema for users and auth sessions
- Backend auth endpoints (login/logout/me)
- User management endpoints with role guard
- Role hierarchy and provisioning logic
- Frontend login gate and simple user management panel
- Seed/bootstrap flow for initial Head account

## 3. Failure modes
- Existing dashboard routes break
- SQLite schema migration errors
- Socket.io auth handshake fails after login
- Seeded default account not created or duplicated
- Role guard too restrictive or too permissive

## 4. Side effects
- Modify backend source files and static frontend
- Add new DB tables in SQLite runtime database
- Possibly update README with usage notes
- Run Node commands to validate syntax and server boot

## 5. Permissions
- Read/write access inside repo and runtime SQLite path
- Execute Node commands in project directory

## Assumptions
- Role hierarchy: Head > CSS Manager > CSS Team Leader > Staff
- Head can manage all users; CSS Manager can manage Team Leaders and Staff in own subtree; Team Leader can manage Staff in own subtree; Staff has read-only self access
- One user can optionally map to a `css_scope` string for future dashboard scoping
