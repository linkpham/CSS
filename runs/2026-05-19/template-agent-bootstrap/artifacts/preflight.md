# Preflight

## Git context

Recent commits observed:

```text
4d1395f added zeus core
1899a87 Update CRM dashboard auth, UI, deploy, and sync workflows
ae1d354 added DEPLOY_SERVER.sh
27ed7c2 Added db schema and SKILL.md
44bf306 docs(readme): rewrite project deployment guide
```

Existing WIP outside this task:

- `.m/session.jsonl`
- `.m/warns.jsonl`
- `.m/.old/`
- `docs/`
- `scripts/`

This run did not modify those paths intentionally.

## Required input availability

| Path | Status | Note |
|---|---:|---|
| `AGENTS.md` | present | Read and applied |
| `template/` | present | Read and updated |
| `CSS/SPW.md` | present | Used as business scope |
| `CSS/spw.md` | present | Duplicate/case variant observed |
| `strongdm.md` | missing | Recorded as future blocker if task needs it |
| `excels/` | missing | Recorded as blocker for real schema/KPI profiling |

## Validation

- `template/AGENTS.md` begins with `**Mục tiêu:**`.
- `template/REQUESTS.md` includes explicit `strongdm.md` and `excels/` blockers.
- `template/scripts/build.sh` passes `bash -n`.
- `template/scripts/deploy.sh` passes `bash -n`.

