# Phase 108

- Chỉnh sửa tính năng: `📋 Báo cáo Sử dụngⓘ` (Xuất báo cáo chi tiết sử dụng gói học theo khoảng thời gian) theo các yêu cầu sau:
Chỉ sửa điều kiện xuất theo `ordles_status`

`
ol.ordles_status = 3
AND ol.ordles_lesson_starttime >= :start_date
AND ol.ordles_lesson_starttime < :end_date
`

tức là cần liệt kê những buổi học COMPLETED 

- Tạo ra 1 file zeus-git.sh để đẩy lên `git@github.com:que-galaxy/zeus-dashboard.git` chỉ với các tệp và thư mục sau:
```
/DEPLOY-SERVER.sh
/docker-compose.prod.sqlite.yml
/docker-compose.prod.yml
/docker-compose.sqlite.yml
/docker-compose.yml
/docker
/README.md
/src
/scripts
``` - Không được phép gửi bất kỳ 1 file hoặc tệp nào khác ngoài danh sách đang cung cấp. Cách dùng như sau: ```zeus-git.sh commit <msg>``` cho việc commit, ```zeus-git.sh push`` cho việc push code. 