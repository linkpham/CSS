# Zeus analytics proposal for learning outcomes & renewal

Date: 2026-05-23
Source used for quick evidence check:
- Production `GET /api/students?download=all`
- Unified CRM dataset backed by Zeus CSI + learner journey + purchase history
- Sample size: 6,391 learners
- CSI target coverage: 6,141 learners

## 1) What the current data already tells us

### Overall snapshot
- Population: **6,391** learners
- Renewed: **273** learners = **4.3%**
- New sale: **216** learners = **3.4%**
- Renewal revenue recognized: **7.375B VND**

### Strongest observed signals linked to poor learning + poor renewal
1. **Low learning pace is the biggest scalable risk pool**
   - `learningPace <= 0.5`: **1,465 learners**
   - renewal rate only **1.5%**
   - avg target score **50.9**

2. **Very low live success is a critical warning sign**
   - `csiSuccessRate <= 50%`: **714 learners**
   - renewal rate only **1.4%**
   - avg target score **17.5**

3. **Bad movement / unhealthy CSI cohorts are weak renewal cohorts**
   - `movementGroup 5 + 6`: **743 learners**
   - renewal rate **1.6%**
   - avg target score **19.1**

4. **Expired / empty-balance learners are a large commercial risk pool**
   - `journeyStatus = Expired`: **1,203 learners**, renewal **2.2%**
   - `remainingSessions <= 0`: **1,271 learners**, renewal **2.1%**

5. **The highest-risk save cohort is learners who both ran out of sessions and are academically weak**
   - `remainingSessions <= 0 AND scoreTarget < 60`: **333 learners**
   - renewal rate only **1.2%**
   - avg pace **0.11**

6. **Class size 1:2 deserves a separate retention lens**
   - `classSizes = 1:2`: **385 learners**
   - renewal rate only **1.0%**
   - despite avg target score still around **83.1**
   - this suggests a non-academic friction or expectation-fit problem worth isolating

### Positive signals worth protecting
1. `learningPace > 2.0`
   - **379 learners**
   - renewal rate **11.3%**

2. `scoreTarget 81-100`
   - **4,735 learners**
   - renewal rate **4.9%**

3. `journeyStatus = Active`
   - **4,938 learners**
   - renewal rate **5.0%**

### Signals that look weak or not yet useful as primary drivers
- `unfinishedRate` currently has too little dispersion in the served dataset
- `teacherDisruptionRate` has limited stable spread and should be used as a supporting signal, not the main ranking driver yet

---

## 2) The most useful analytics to build next

Below is the prioritized list based on actionability, current data availability in Zeus, and expected business value.

## Priority A — should build first

### A1. Renewal readiness matrix
**Goal**
- Tell CSS exactly who is most likely to renew, who needs saving, and who is not commercially ready yet.

**Use data already available**
- `remainingSessions`
- `journeyStatus`
- `scoreTarget`
- `movementGroup`
- `learningPace`
- `latestOrderAmount`, `latestOrderDate`
- `renewalStatus`, `renewalRevenue`
- `packageGroups`, `classSizes`, `css`

**Core analysis**
Create a 2D / 3D segmentation:
- X-axis: session balance bucket
  - `>16`, `9-16`, `5-8`, `1-4`, `0`
- Y-axis: learning health
  - `scoreTarget category`
- overlay: `movementGroup` and `learningPace`

**Most important business slices**
- `remain = 0 AND target >= 60` → ready-to-close renewal
- `remain = 0 AND target < 60` → save-risk renewal
- `remain 1-4 AND pace <= 0.5` → urgent intervention before learner silently expires
- `remain > 16 AND pace <= 0.5` → large package but poor usage, future churn risk

**Why this matters**
- Right now zero-balance learners are too broad. This matrix separates:
  - commercially ready
  - academically not ready
  - at-risk but still salvageable

**Recommended UI output**
- heatmap / matrix
- drill-down list per cell
- action recommendations per cell

---

### A2. Save-risk cohort analysis
**Goal**
- Find the learners who are still recoverable if the team intervenes now.

**Use data**
- `scoreTarget`, `scoreBase`, `variance`, `movementGroup`
- `learningPace`
- `csiSuccessRate`
- `remainingSessions`
- `scheduledSessions`, `unscheduledSessions`, `cancelledSessions`

**Core logic**
Define a recovery-priority score using:
- low target score
- negative movement or prolonged danger
- low live success
- low pace
- few remaining sessions or many unscheduled sessions

**Why this matters**
The most actionable cohort is not just “bad students”; it is:
- bad but still active,
- still has session balance,
- still enough room to recover before renewal conversation.

**Suggested high-priority cohort**
- `target < 60`
- `pace <= 0.5`
- `remainingSessions > 0`
- `journeyStatus = Active`

This is more useful for coaching ops than looking at target score alone.

---

### A3. Activation & early adoption funnel
**Goal**
- Improve onboarding and detect which new learners are likely to become low-quality or low-renewal learners very early.

**Use data**
- `journeyStatus`
- `firstLessonStarttime`
- `latestOrderDate`
- `scheduledSessions`, `unscheduledSessions`, `completedSessions`
- `movementGroup = 7. Mới từ Zeus (chưa có base)`
- `activationSpeed`
- `classSizes`, `packageGroups`, `css`

**Core analysis**
Track funnel:
1. bought package
2. first lesson scheduled
3. first lesson completed
4. first 4 sessions completed
5. first CSI score available
6. enters healthy / at-risk state

**Why this matters**
Current evidence shows:
- onboarding cohorts have low renewal because they are not yet at renewal stage,
- but they are the cleanest place to improve future outcomes.

**Best business use**
- identify which CSS / package group / class model has the slowest early activation
- build “first 14 days” intervention rules

---

### A4. Live-learning friction analysis
**Goal**
- Isolate whether poor outcome is caused by attendance / scheduling / live execution friction.

**Use data**
- `csiSuccessRate`
- `scheduledSessions`, `unscheduledSessions`, `cancelledSessions`, `completedSessions`
- `teacherDisruptionRate`, `teacherDisruptionCumulative`
- `classSizes`
- `packageGroups`

**Core analysis**
Compare outcome and renewal by combinations such as:
- low live success + high unscheduled
- low live success + high cancelled
- low live success + high teacher disruption
- class `1:1` vs `1:2`

**Why this matters**
The data already suggests `1:2` has weak renewal despite decent target score, which may indicate:
- expectation mismatch,
- scheduling friction,
- learner engagement model mismatch,
- or parent perception issue.

This analysis can tell whether the problem is product design, teacher delivery, or scheduling ops.

---

## Priority B — high value after A is live

### B1. Package group renewal playbook analysis
**Goal**
- Understand which program families need different retention plays.

**Use data**
- `packageGroups`
- `classSizes`
- `scoreTarget`, `movementGroup`, `learningPace`
- `remainingSessions`
- `renewalStatus`, `renewalRevenue`

**Why useful**
Current data shows meaningful variation by package group:
- `SpeakWell Get Ready` lower renewal
- `SpeakWell Hero / Teens` different behavior
- `Easy Speak for Adults` much higher renewal but small base

**What to build**
For each package group:
- renewal rate
- avg target score
- pace
- expiry pressure
- save-risk mix
- recommended commercial script / academic script

---

### B2. CSS portfolio effectiveness analysis
**Goal**
- Help managers see which CSS portfolios are improving outcomes and renewals, not just handling volume.

**Use data**
- `css`
- all learning / renewal fields above

**Metrics to show**
- % healthy learners
- % bad movement
- % pace <= 0.5
- zero-balance rate
- renewal conversion among zero-balance learners
- save-risk recovery rate month-over-month

**Why useful**
This enables coaching CSS by portfolio quality, not only raw learner count.

---

### B3. Learner value at risk
**Goal**
- Quantify how much future revenue is sitting in unhealthy cohorts.

**Use data**
- `latestOrderAmount`
- `remainingSessions`
- `renewalRevenue`
- `scoreTarget`, `movementGroup`, `learningPace`

**Core output**
Estimate:
- revenue at risk from unhealthy active learners
- revenue at risk from zero-balance low-score learners
- protected revenue from healthy cohorts close to expiry

**Why useful**
This turns academic health into a finance-prioritized retention queue.

---

## Priority C — useful but not first wave

### C1. Teacher-impact deep dive
Useful once disruption fields stabilize more.

### C2. Parent / contact-history next best action
High value, but requires stronger CS notes / call logs / touchpoint data.

### C3. Predictive propensity model
Possible later, but not needed before the rule-based matrices above are live.

---

## 3) Recommended implementation order

### Phase 1 — fastest value in current CRM
1. **Renewal readiness matrix**
2. **Save-risk cohort queue**
3. **Activation funnel**
4. **Live-learning friction analysis**

### Phase 2
5. **Package group renewal playbooks**
6. **CSS portfolio effectiveness**
7. **Learner value at risk**

### Phase 3
8. Teacher-impact deep dive
9. Predictive model / scoring layer

---

## 4) Exact dashboards I recommend adding to the CRM

### Dashboard A — Renewal Command Center
Widgets:
- renewal readiness matrix
- zero-balance learners split by health
- next-30-day renewal queue
- save-risk queue
- revenue at risk

### Dashboard B — Learning Recovery Board
Widgets:
- low pace queue
- bad movement queue
- low live-success queue
- active but deteriorating learners
- intervention result tracking

### Dashboard C — Onboarding & Activation
Widgets:
- first lesson delay
- unscheduled after purchase
- first 4-session completion
- early CSI emergence
- onboarding drop-off by CSS / package / class size

### Dashboard D — Product / Class Model Review
Widgets:
- renewal by class size
- renewal by package group
- live success by class size
- target score by class size
- cancellation / unscheduled patterns by class size

---

## 5) Immediate actions I recommend from the current data

### High-value operational queues to build immediately
1. **Queue 1: zero balance + target >= 60**
- commercial-ready renewal
- likely highest near-term close efficiency

2. **Queue 2: zero balance + target < 60**
- not a pure sales queue
- needs combined academic rescue + renewal strategy

3. **Queue 3: active + pace <= 0.5 + remaining > 0**
- biggest preventable churn cohort
- intervene before expiry

4. **Queue 4: live success <= 50%**
- severe execution-risk cohort
- needs scheduling / teacher / engagement diagnosis

5. **Queue 5: class size 1:2 with weak renewal**
- special review cohort
- likely product-fit / expectation issue rather than pure score issue

---

## 6) Final recommendation
If we want the **highest business impact using the Zeus data already available**, the best next build is:

1. **Renewal readiness matrix**
2. **Save-risk cohort queue**
3. **Activation funnel**
4. **Live-learning friction analysis**

These four analyses are already well-supported by the current Zeus-backed data and directly connect to:
- better academic recovery,
- faster CSS intervention,
- clearer renewal prioritization,
- and higher retained revenue.
