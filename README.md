# DuckMind-Style Agent Workspace

This repo describes how humans delegate work to software development agents following the DuckMind philosophy:
- Start from a **clear spec (seed)**.
- Evaluate with **behavioral scenarios (validator)**.
- Use a **feedback loop** to improve across runs.
- Store state on the **filesystem** for inspection and replay.

---
## 🚀 Getting Started

Open this repo in your coding agent (Claude Code, Cursor, Codex, etc.) and say:
```
Read init.md and follow the instructions to scaffold the project workspace.
```

The agent will use `init.md` to generate the full template structure so you can immediately start building software with AI.

---
## 1) How to Give Instructions to the Agent (for humans)

When creating a new task, submit it using the following template:

```md
## [Task Name]

### 1. Objective
- What do you want the system to do?

### 2. Input
- What is the input data? What format?

### 3. Expected Output
- What file/report/behavior must the agent produce?

### 4. Acceptance Criteria
- Specific pass/fail conditions.
- Which scenarios are mandatory to run?

### 5. Constraints
- Which directories must not be modified?
- Runtime/cost/dependency limits?

### 6. Permitted Side Effects
- Which files may be created or modified?
- Which commands may be executed?

### 7. Access Permissions
- What read/write/execute scope does the agent have?
```

> Tip: The more specific the instruction, the better the agent performs in "non-interactive" mode.

---

## 2) Rules for Good Instructions

1. **Describe desired behavior**, not just "fix the code".
2. **Provide input/output examples** wherever possible.
3. **State what is forbidden** (no API changes, no new dependencies, etc.).
4. **Always include pass/fail criteria** so the agent can self-verify.
5. For large tasks, split into smaller independent subtasks.

---

## 3) Recommended Directory Structure for Agent Dev

```text
.
├── AGENTS.md
├── README.md
├── docs/
│   ├── philosophy/
│   ├── specs/
│   ├── scenarios/
│   │   ├── holdout/
│   │   └── regression/
│   └── runbooks/
├── agent/
│   ├── prompts/
│   ├── policies/
│   ├── tool-contracts/
│   └── checklists/
├── src/
├── tests/
│   ├── unit/
│   ├── integration/
│   └── e2e/
├── dtu/
│   ├── twins/
│   ├── fixtures/
│   └── replay/
├── scripts/
│   ├── validate.sh
│   ├── scenario.sh
│   └── replay.sh
├── runs/
│   └── YYYY-MM-DD/<run-id>/
└── memory/
    ├── index.md
    ├── decisions/
    └── summaries/
```

---

## 4) Short Instruction Example

```md
Task: Export CSV report by date range

Objective:
- Add endpoint `GET /reports/export?from=...&to=...` returning a CSV file.

Acceptance Criteria:
- CSV has headers: id, name, created_at, status
- Returns 400 if from > to
- Returns 200 + CSV file if valid
- Has integration tests for 3 cases: success, invalid range, empty data

Constraints:
- Do not change the DB schema
- Do not add external libraries
```

---

## 5) Recommended Workflow

1. Human creates spec in `docs/specs/`.
2. Agent maps spec → scenario in `docs/scenarios/holdout/`.
3. Agent executes, runs validator, and saves run trace in `runs/`.
4. Human reviews results based on behavior + run artifacts.