# Add first lesson date to learner detail views

Date: 2026-05-25
Production: `https://crm.icanwork.vn`

## Change
Added `ngày bắt đầu buổi học đầu tiên` to both learner detail surfaces on CRM root:
1. learner detail section inside `Hồ sơ học viên`
2. popup `Thông tin chi tiết học viên`

## UI changes
### Hồ sơ học viên
Added a new package-stat block:
- label: `Buổi học đầu tiên`
- value source: `journeyFirstLessonDate`
- display format: `formatDateValue(student.firstLessonStarttime)`

### Popup chi tiết học viên
Added a new package-stat block:
- label: `Buổi học đầu tiên`
- value source: `studentDetailFirstLessonDate`
- display format: `formatDateValue(student.firstLessonStarttime)`

## Data source
The backend already exposed:
- `firstLessonStarttime`
- `lastLessonStarttime`

So no backend schema/API change was required for this step.

## Production verification
Verified root HTML contains:
- `studentDetailFirstLessonDate`
- `journeyFirstLessonDate`
- `Buổi học đầu tiên`
- `Ngày bắt đầu buổi học đầu tiên`

Verified API sample:
- `/api/students/1746/detail`
- student: `Khánh Đoàn`
- `firstLessonStarttime = 2026-01-05 12:10:00`
