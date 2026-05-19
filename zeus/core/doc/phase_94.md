# Phase 94

## Yêu cầu
- Tổ chức lại `Phiếu ghi theo nguồn`, chart `Xu hướng cuộc gọi`, `Cuộc gọi theo giờ`, `Xu hướng chat` sao cho đẹp nhất, không được để:
```
1. Vỡ layout
2. Vỡ ngữ cảnh
3. Vỡ liên kết dữ liệu
4. Vỡ trải nghiệm đọc
```

## Kết quả

### Phân tích vấn đề

Layout cũ đặt 4 chart vào 2 hàng đều nhau (2 cột 50/50), không theo ngữ cảnh:
- Hàng 1: `Phiếu ghi theo nguồn` (doughnut) | `Xu hướng cuộc gọi` (line)
- Hàng 2: `Cuộc gọi theo giờ` (bar) | `Xu hướng chat` (line)

**Vấn đề:**
1. Chart cuộc gọi bị tách ra 2 hàng khác nhau → vỡ ngữ cảnh
2. Doughnut chart chiếm 50% width không cần thiết → vỡ layout
3. Phiếu ghi và chat (cùng nhóm "tương tác khách hàng") bị tách → vỡ liên kết dữ liệu
4. Người đọc phải nhảy qua lại giữa các hàng để so sánh → vỡ trải nghiệm đọc

### Giải pháp: Tổ chức theo nhóm ngữ cảnh

Chia thành 2 section rõ ràng với section header:

**Section 1 — "Tổng quan Phiếu ghi & Chat"** (accent bar tím):
| Vị trí | Chart | Loại | Tỷ lệ |
|--------|-------|------|--------|
| Trái | Phiếu ghi theo nguồn | Doughnut | 5/12 |
| Phải | Xu hướng chat | Line (gặp/nhỡ) | 7/12 |

**Section 2 — "Phân tích Cuộc gọi"** (accent bar xanh):
| Vị trí | Chart | Loại | Tỷ lệ |
|--------|-------|------|--------|
| Trái | Xu hướng cuộc gọi | Line (gặp/nhỡ) | 7/12 |
| Phải | Cuộc gọi theo giờ | Bar 24h | 5/12 |

### Chi tiết thay đổi

1. **Section headers**: Thêm label nhóm với accent bar màu (tím cho Phiếu ghi & Chat, xanh cho Cuộc gọi) giúp phân biệt ngữ cảnh rõ ràng
2. **Grid 12 cột**: Chuyển từ `grid-cols-2` sang `grid-cols-12` để kiểm soát tỷ lệ chính xác hơn
3. **Tỷ lệ thông minh**: Doughnut và Bar chart compact (5/12), Line trend rộng hơn (7/12)
4. **Canvas height**: Tăng từ 240 → 260px cho không gian hiển thị tốt hơn
5. **Responsive**: Mobile vẫn stack thành 1 cột (`grid-cols-1`) nhờ prefix `lg:`

### Kiểm tra 4 tiêu chí

| Tiêu chí | Trước | Sau |
|-----------|-------|-----|
| Vỡ layout | Doughnut 50% quá rộng | 5/12 vừa đủ, line trend 7/12 thoáng |
| Vỡ ngữ cảnh | Call charts tách 2 hàng | Cùng section "Phân tích Cuộc gọi" |
| Vỡ liên kết dữ liệu | Phiếu ghi & Chat tách xa | Cùng section "Tổng quan Phiếu ghi & Chat" |
| Vỡ trải nghiệm đọc | Nhảy qua lại giữa hàng | Đọc từng section từ trên xuống, mạch lạc |

### Files đã thay đổi
- `src/resources/views/caresoft/index.blade.php` — Tổ chức lại layout 4 chart

## Status: ✅ HOÀN THÀNH
