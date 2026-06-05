# First lesson enhancement

Date: 2026-05-25
Production: `https://crm.icanwork.vn`

## Delivered
Completed both follow-up improvements:
1. improved popup/detail presentation for `Buổi đầu tiên / Buổi gần nhất`
2. added first-lesson information into the `Danh sách học viên` table

## UI changes
### Popup chi tiết học viên
Updated the stat block from only `Buổi học đầu tiên` to:
- label: `Buổi đầu / gần nhất`
- value: first lesson date
- subtext: `Gần nhất: ...`

### Hồ sơ học viên
Updated the learner detail stat similarly:
- label: `Buổi đầu / gần nhất`
- value: first lesson date
- subtext: latest lesson date

### Danh sách học viên
Added a new table column:
- `Buổi đầu tiên`
- main line: first lesson date
- muted line: `Gần nhất: ...`

## Production verification
Verified root HTML contains:
- `studentDetailLessonTimeline`
- `journeyFirstLessonMeta`
- `Buổi đầu / gần nhất`
- `Buổi đầu tiên`
- `Gần nhất:`

This confirms both popup/detail and student list enhancements are live.
