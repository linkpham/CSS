# LCMS DB audit and real data extraction

Date: 2026-05-26
Scope: Zeus live DB / SpeakWell LCMS data

## Conclusion
LCMS real data does exist in Zeus live DB. The current CRM `LCMS proxy` is not because data is absent, but because CRM has not yet been wired to the real LCMS tables/service.

## Real LCMS tables confirmed in Zeus
Core tables found:
- `lcms_user_assignments`
- `lcms_students`
- `lcms_courses`
- `lcms_student_scores`

Related quiz tables also exist:
- `lcms_user_quiz_grades`
- `tbl_quiz_attempts`
- `tbl_quizzes`
- `tbl_quiz_attempts_questions`

## Key schema fields confirmed
### `lcms_user_assignments`
- `usrasi_course_id`
- `usrasi_section_id`
- `usrasi_student_id`
- `usrasi_completion_time`
- `usrasi_completion_state`
- `usrasi_payload_data`
- `usrasi_response_data`

### `lcms_students`
- `stu_id`
- `stu_name`
- `stu_email`
- `stu_user_id`
- `stu_gender`

### `lcms_courses`
- `cou_id`
- `cou_name`
- `cou_type`
- `cou_parent_id`
- `cou_section_type`

### `lcms_student_scores`
- `stusco_student_id`
- `stusco_user_id`
- `stusco_course_id`
- `stusco_overall_score`

## SpeakWell LCMS scope already encoded in Zeus service
`App\Services\LcmsService` is already present and uses course IDs:
- `346` = Kid's Box - Beginners (New Programme)
- `563` = Kid's Box - Starters
- `595` = Kid's Box - Movers
- `1084` = Kid's Box - Flyers

## Live DB counts
For SpeakWell LCMS courses only:
- `total_assignments = 227319`
- `unique_lcms_students = 4932`
- `unique_sections = 294`
- `unique_courses = 4`

By section type:
- homework (`cou_section_type = 2`):
  - rows = `223645`
  - unique sections = `286`
- test (`cou_section_type = 3`):
  - rows = `3674`
  - unique sections = `8`

## Live overview stats from Zeus `LcmsService`
- homework:
  - total_sections = `223645`
  - completed_sections = `35114`
  - completion_ratio = `15.7%`
  - avg_score = `7.27`
- test:
  - total_sections = `3674`
  - completed_sections = `720`
  - completion_ratio = `19.6%`
  - avg_score = `6.78`
- total_students = `4932`

## Real student samples extracted
### Batch sample by Zeus `user_id`
- `10184` -> `hw_completion_rate = 100`, `hw_avg_score = 10`, `test_avg_score = null`
- `10409` -> `hw_completion_rate = 100`, `hw_avg_score = 8.64`, `test_avg_score = null`
- `6741` -> `hw_completion_rate = 61.5`, `hw_avg_score = 10`, `test_avg_score = null`
- `5545` -> `hw_completion_rate = 0`, `hw_avg_score = 0.35`, `test_avg_score = null`
- `1746` -> `hw_completion_rate = 0`, `hw_avg_score = null`, `test_avg_score = null`

### Deep detail sample
Using LCMS student ID `8960` (mapped to Zeus user `6741`):
- student: `Diệp Linh Hoàng`
- course: `Kid's Box - Starters`
- homework total sections: `26`
- completed: `16`
- completion ratio: `61.54%`
- avg score: `10`
- section-level detail includes section name, completion flag, completion time, and score.

## Important mapping caveat
There are 2 IDs in play:
- Zeus Core `tbl_users.user_id`
- LCMS `lcms_students.stu_id`

`getStudentLcmsSummary()` uses Zeus `user_id` via `stu_user_id` mapping.
`getStudentDetailReport()` expects LCMS `stu_id`.

This means if CRM wants deep per-student LCMS detail, it should either:
1. map Zeus `user_id` -> `lcms_students.stu_id` first, or
2. add a wrapper endpoint in Zeus that accepts Zeus `user_id` directly.

## Existing Zeus code already available
Real LCMS service/controller already exists in Zeus:
- `zeus/core/src/app/Services/LcmsService.php`
- `zeus/core/src/app/Http/Controllers/DashboardController.php`
- `zeus/core/src/app/Http/Controllers/CsiController.php`

`CsiController` already enriches CSI student responses with:
- `hw_completion_rate`
- `hw_avg_score`
- `test_avg_score`

## Practical next step
If we want CRM root to stop using LCMS proxy, the cleanest path is:
1. reuse Zeus `LcmsService` logic,
2. expose or consume summary + batch stats by Zeus `user_id`,
3. map deep-detail requests through `lcms_students.stu_id` when needed.
