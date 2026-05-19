# REQUESTS.md

## Phạm vi hiện tại

Dự án hiện tập trung vào một hệ thống CRM Dashboard để theo dõi sức khỏe học tập, hiệu quả chăm sóc học viên, tình trạng gia hạn và dự báo doanh thu.

Kiến trúc cần giữ theo hướng:

```text
Google Sheet dữ liệu gốc
        ↓
Đồng bộ dữ liệu riêng
        ↓
Database nội bộ
        ↓
Dashboard + Phân quyền người dùng
```

Nguyên tắc quan trọng:

- Không để dashboard đọc trực tiếp Google Sheet khi người dùng mở trang.
- Dữ liệu phải đi qua bước đồng bộ trước để hệ thống ổn định, nhanh và dễ kiểm soát.

---

## Mục tiêu nghiệp vụ chính

### 1. Dashboard CRM / CSI

Hệ thống cần hỗ trợ:

- Theo dõi tổng quan sức khỏe học tập của học viên.
- Phân tích xu hướng cải thiện hoặc trượt dốc.
- Theo dõi hiệu quả chăm sóc theo CSS.
- Theo dõi tình trạng gia hạn.
- Hỗ trợ forecast doanh thu.
- Cho phép lọc dữ liệu theo nhiều chiều.
- Xuất báo cáo PDF từ dashboard.

### 2. Quản lý người dùng

Hệ thống cần có quản lý người dùng theo 4 cấp độ:

- Head
- CSS Manager
- CSS Team Leader
- Staff

Mục tiêu:

- Có đăng nhập tập trung.
- Có thể cấp phát tài khoản theo cơ cấu quản lý.
- Tạo nền tảng để phân quyền dữ liệu và mở rộng sau này.

### 3. Vận hành production

Hệ thống cần vận hành ổn định trên domain:

```text
https://crm.icanwork.vn
```

Yêu cầu:

- Truy cập được qua HTTPS.
- Có cơ chế đồng bộ dữ liệu định kỳ.
- Có môi trường production tách biệt với máy local.

---

## Trạng thái đã hoàn thành

### 1. Nguồn dữ liệu

- Đã chốt dùng một nguồn dữ liệu chính từ Google Sheet hiện tại.
- Dữ liệu đã được chuẩn hóa để phục vụ dashboard CRM.
- Không dùng lại mô hình cũ hoặc dữ liệu cũ ngoài phạm vi đã thống nhất.

### 2. Cơ chế đồng bộ dữ liệu

- Đã tách riêng bước đồng bộ dữ liệu khỏi dashboard.
- Dashboard chỉ làm việc với dữ liệu đã được đồng bộ sẵn.
- Việc này giúp hệ thống nhanh hơn và tránh phụ thuộc trực tiếp vào Google khi người dùng truy cập.

### 3. Dashboard nghiệp vụ

Dashboard hiện đã có các nhóm nội dung chính:

- Tổng quan học viên
- Chỉ số sức khỏe học tập
- Nhóm chuyển dịch sức khỏe
- Hiệu quả chăm sóc
- Tình trạng gia hạn
- Forecast doanh thu

### 4. Bộ lọc và báo cáo

Đã hỗ trợ các bộ lọc chính như:

- thời gian
- CSS phụ trách
- nhóm sức khỏe
- nhóm chuyển dịch
- trạng thái gia hạn
- vòng đời học viên

Đã có tính năng:

- xuất PDF từ dashboard hiện tại

### 5. Đăng nhập và phân quyền

Đã bổ sung:

- màn hình đăng nhập
- quản lý user theo 4 cấp độ
- phân quyền cơ bản theo cấp quản lý
- cấp phát user để phục vụ triển khai thực tế

### 6. Triển khai production

Đã deploy thành công hệ thống lên domain:

```text
https://crm.icanwork.vn
```

Trạng thái đã xác minh:

- truy cập public thành công
- đăng nhập hoạt động
- dashboard hoạt động
- đồng bộ dữ liệu hoạt động

---

## Quy tắc quản lý người dùng

Cấu trúc role hiện tại:

- **Head**: quản lý toàn bộ hệ thống
- **CSS Manager**: quản lý các nhóm dưới quyền
- **CSS Team Leader**: quản lý staff trong nhóm
- **Staff**: sử dụng hệ thống theo phạm vi được cấp

Nguyên tắc:

- Role cao hơn có quyền quản lý role thấp hơn.
- Không cho role thấp tạo hoặc quản lý role cao hơn quyền của mình.
- Hệ thống cần sẵn sàng để mở rộng phân quyền dữ liệu chi tiết hơn trong giai đoạn sau.

---

## Cách vận hành hiện tại

### 1. Về dữ liệu

- Dữ liệu gốc vẫn nằm ở Google Sheet.
- Hệ thống sẽ đồng bộ dữ liệu từ nguồn này vào database nội bộ.
- Dashboard hiển thị từ dữ liệu đã đồng bộ.

### 2. Về người dùng

- Người dùng phải đăng nhập mới truy cập dashboard.
- Quyền xem và quyền quản lý sẽ phụ thuộc vào role.

### 3. Về production

- Hệ thống đang chạy online trên domain chính thức.
- Có thể tiếp tục cấu hình thêm user thật và phân quyền vận hành thực tế.

---

## Những việc không được làm trong phase hiện tại

- Không để dashboard đọc Google Sheet trực tiếp khi mở trang.
- Không bỏ cơ chế đăng nhập ở môi trường production.
- Không làm sai cấu trúc phân quyền 4 cấp đã thống nhất.
- Không để dữ liệu vận hành phụ thuộc vào thao tác thủ công trên local.
- Không đưa các thông tin nhạy cảm ra tài liệu công khai.

---

## Việc nên làm tiếp theo

### 1. Hoàn thiện quản lý người dùng

- thêm đổi mật khẩu
- thêm reset mật khẩu
- thêm tìm kiếm/lọc danh sách user
- thêm lịch sử thao tác quản trị user

### 2. Hoàn thiện phân quyền dữ liệu

- Head xem toàn bộ
- Manager xem dữ liệu của team mình
- Team Leader xem dữ liệu của nhóm mình
- Staff xem đúng phạm vi cá nhân được cấp

### 3. Nâng cấp dashboard

- xuất Excel/CSV
- xem drill-down danh sách học viên
- tách dashboard thành các nhóm màn hình rõ hơn

### 4. Vận hành ổn định hơn

- bổ sung hướng dẫn vận hành
- bổ sung backup dữ liệu
- bổ sung monitoring cơ bản
- bổ sung kiểm thử cho các luồng chính

### 5. Bảo mật production

- thay tài khoản mặc định bằng tài khoản thật
- chuẩn hóa quản lý mật khẩu và quyền truy cập
- siết chặt cấu hình production khi cần
