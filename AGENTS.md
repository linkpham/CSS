# AGENTS.md

Agent operating guide following the DUCKMIND AI philosophy, prioritizing:

1. **Seed → Validator → Feedback loop**
2. **Validate through end-to-end behavior, not code reading**
3. **Filesystem-first memory: all important state must be written to files**
4. **Replayable runs: clear logs, artifacts, and results**

---

## 1) Working Contract (5 mandatory questions)

Before executing any task, the agent must fully describe:

1. **Input**: What does the user provide? In what format?
2. **Output**: What does the result consist of?
3. **Failure modes**: In what ways can this break?
4. **Side effects**: Which files are created/modified, which commands are run, what state changes?
5. **Permissions**: What read/write/execute access is required and where?

If any item is missing, the agent **must not guess** and must request clarification.

---

## 2) Standard Directory Structure for Software Agents

```text
.
├── AGENTS.md
├── README.md
├── docs/
│   ├── philosophy/                  # Principles summary, architecture, ADRs
│   ├── specs/                       # Seed: PRD/RFC/feature contract
│   ├── scenarios/
│   │   ├── holdout/                 # Independent scenarios, anti-reward-hacking
│   │   └── regression/              # Regression scenarios for past failures
│   └── runbooks/                    # Operations procedures and incident handling
├── agent/
│   ├── prompts/                     # Prompt templates by task type
│   ├── policies/                    # Guardrails, permissions, and limits
│   ├── tool-contracts/              # I/O contracts for scripts/tools
│   └── checklists/                  # Pre/post checklists for each run
├── src/                             # Main source code
├── tests/
│   ├── unit/
│   ├── integration/
│   └── e2e/
├── dtu/                             # Digital Twin Universe (if external integrations exist)
│   ├── twins/                       # Behavioral clones of third-party APIs
│   ├── fixtures/
│   └── replay/
├── scripts/
│   ├── validate.sh                  # Run the full system validator
│   ├── scenario.sh                  # Run a single scenario by ID
│   └── replay.sh                    # Replay a previously saved run
├── runs/
│   └── YYYY-MM-DD/
│       └── <run-id>/
│           ├── input.md             # Original human instruction
│           ├── contract.md          # 5 questions + assumptions
│           ├── plan.md              # Execution plan
│           ├── commands.log         # Commands executed
│           ├── artifacts/           # Reports, snapshots, intermediate outputs
│           └── result.json          # Final state + summary
└── memory/
    ├── index.md                     # Current knowledge index
    ├── decisions/                   # Technical decisions and trade-offs
    └── summaries/                   # Multi-layer summaries (pyramid summaries)
```

---

## 3) Standard Operating Procedure

1. Receive instructions from `README.md` or `docs/specs/*`.
2. Write `contract.md` (5 questions + assumptions + risks).
3. Select or create scenarios in `docs/scenarios/holdout/*` before writing code.
4. Draft a short plan in `plan.md`.
5. Execute changes in `src/` and update tests.
6. Run the validator and related scenarios.
7. Save all logs and artifacts to `runs/<date>/<run-id>/`.
8. Only close the task when holdout scenarios pass and the run is replayable.

---

## 4) Definition of Done

A task is only complete when all of the following are satisfied:

- A clear, unambiguous seed/spec exists.
- A behavior-level validator (scenario/e2e) is included.
- Results pass consistently, with no flakiness.
- A complete run trace exists for others to replay.
- Important technical decisions have been recorded.
- No permissions were used beyond what was necessary.