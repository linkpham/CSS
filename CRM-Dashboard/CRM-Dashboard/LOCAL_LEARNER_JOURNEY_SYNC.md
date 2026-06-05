# Local Learner Journey Sync

## Nguồn JSON snapshot
```bash
cd /mnt/f/Code/strongdm-main/CRM-Dashboard/CRM-Dashboard
LEARNER_JOURNEY_SOURCE=json \
LEARNER_JOURNEY_JSON_PATH=/mnt/f/Code/strongdm-main/runs/2026-05-20/zeus-learner-journey/zeus_1_1_1_2_students.json \
node src/scripts/syncLearnerJourney.js
```

## Sync trực tiếp Zeus MySQL qua SSH tunnel
```bash
cd /mnt/f/Code/strongdm-main/CRM-Dashboard/CRM-Dashboard
npm run sync:learner-journey:mysql:tunnel
```

Script sẽ:
1. copy SSH key sang `/tmp` với quyền an toàn
2. đọc `DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD` từ `.env` của Zeus app trên server
3. mở SSH tunnel local `127.0.0.1:13306 -> Zeus DB`
4. chạy `node src/scripts/syncLearnerJourney.js` với `LEARNER_JOURNEY_SOURCE=mysql`

## API local dùng cho tab Learner Journey
- `GET /api/learner-journey/students`

Nguồn dữ liệu của endpoint này là bảng local SQLite:
- `learner_journey_students`

Bảng này khác với `dashboard_data` và được thiết kế riêng cho learner journey để hiển thị đúng:
- class size 1:1 / 1:2
- package names
- purchased sessions
- scheduled / unscheduled / completed / cancelled
- remaining sessions
