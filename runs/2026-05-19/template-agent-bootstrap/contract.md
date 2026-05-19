# Contract

## 1. Input

- User prompt in Markdown.
- Existing repo files:
  - `AGENTS.md`
  - `template/README.md`
  - `template/REQUESTS.md`
  - `template/docs/specs/SPEC_TEMPLATE.md`
  - `template/docs/runbooks/REGRESSION_TEMPLATE.md`
  - `template/scripts/deploy.sh`
  - `template/config/discord.cnf.example`
  - `CSS/SPW.md`

Missing but referenced:

- `strongdm.md`
- `excels/`

## 2. Output

- Updated `template/REQUESTS.md`.
- New `template/AGENTS.md`.
- New `template/scripts/build.sh`.
- Updated `template/scripts/deploy.sh`.
- Replayable run trace in `runs/2026-05-19/template-agent-bootstrap/`.

## 3. Failure modes

- If future agents assume Excel schema without `excels/`, they may build wrong KPIs.
- If `strongdm.md` is required but missing, access/security instructions may be incomplete.
- If template scripts are too project-specific, they may break new projects.
- If missing credentials are logged, secrets could leak.

## 4. Side effects

- File writes limited to `template/` and `runs/`.
- Commands executed:
  - `git status --short`
  - `git diff --stat`
  - `git log --oneline -5`
  - bounded file reads/searches
  - `chmod +x template/scripts/build.sh template/scripts/deploy.sh`
  - `bash -n` validation for scripts

## 5. Permissions

- Read access: repo files under `/Users/que/Projects/CSS`.
- Write access: `template/` and `runs/2026-05-19/template-agent-bootstrap/`.
- Execute access: local shell validation only.
- No sudo, no network, no secret access.

## Assumptions

- Because `strongdm.md` and `excels/` were absent, the template must treat them as required inputs/blockers for future tasks rather than inventing content.
- `CSS/SPW.md` is the active business spec for dashboard scope.

