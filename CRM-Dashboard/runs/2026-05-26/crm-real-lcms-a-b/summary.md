# CRM real LCMS integration (A then B)

Date: 2026-05-26
App: `https://crm.icanwork.vn`

## Objective
Replace LCMS proxy with real Zeus LCMS data in two steps:
- A: real LCMS summary in CRM
- B: real LCMS deep detail in learner profile and student popup

## Backend changes
### New service
Added:
- `src/services/lcmsService.js`

Capabilities:
- `getStudentLcmsStatsBatch(userIds)`
- `getStudentLcmsDetailByUserId(userId)`

Uses real Zeus tables:
- `lcms_user_assignments`
- `lcms_students`
- `lcms_courses`
- `lcms_student_scores`

### Unified student data
Updated `src/app.js`:
- LCMS batch cache added
- unified student universe now enriches each learner with:
  - `lcmsHomeworkCompletionRate`
  - `lcmsHomeworkAvgScore`
  - `lcmsTestAvgScore`
  - `lcmsHasRealData`
- student detail endpoint now returns:
  - `lcms`

## Frontend changes
Updated `public/index.html`:
- wording changed from LCMS proxy to LCMS thật where relevant
- learner profile LCMS tab now prefers real LCMS summary
- student detail popup now includes `Chi tiết LCMS thật`
- learner profile LCMS tab now includes `Chi tiết section LCMS thật`
- fallback note shown when learner has no real LCMS coverage

## Production verification
### Student list payload
`/api/students?download=all`
- student rows now include:
  - `lcmsHomeworkCompletionRate`
  - `lcmsHomeworkAvgScore`
  - `lcmsTestAvgScore`
  - `lcmsHasRealData`

### Coverage
Sample production checks showed broad LCMS coverage on the unified learner population.
Live detail checks succeeded for real learners with mapped LCMS records.

### Deep-detail check
`GET /api/students/6741/detail`
- `lcms.available = true`
- summary:
  - `hwCompletionRate = 61.5`
  - `hwAvgScore = 10`
  - `testAvgScore = null`
- section rows returned: `26`
- first section example:
  - `Unit 1 - Lesson 1 - Vocabulary and Listening`
  - `BTVN`
  - completed = `true`
  - score = `10`

### HTML markers verified live
- `LCMS hiện ưu tiên <strong>dữ liệu thật từ Zeus</strong>`
- `Hồ sơ học viên · LCMS thật`
- `Chi tiết section LCMS thật`
- `studentDetailLcmsTable`
- `homeworkLcmsDetailTable`

## Result
A and B are both now live:
- summary LCMS thật is wired into CRM student data
- deep per-student LCMS detail is exposed in popup and learner profile

## Remaining caveat
When a learner has no real LCMS mapping/coverage, the UI falls back to the previous proxy signals instead of showing blank data.
