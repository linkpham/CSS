# Ticket compare audit after source update

Date: 2026-05-25
Module: `https://crm.icanwork.vn/ticket/`

## Scope audited
- year comparison between 2025 and 2026
- category / cause / pattern comparison blocks
- wording risk after switching 2025 to SpeakWell-only baseline and 2026 to combined sources

## Verified data snapshot
Using production dashboard after source update:
- total rowCount: `4740`
- 2025 total: `2093`
- 2026 total: `2647`

### Source composition by year
- 2025
  - `Nguồn dữ liệu 2025 - SpeakWell`: `2093`
- 2026
  - `Nguồn bổ sung 2026 - BOS`: `1851`
  - `Nguồn dữ liệu 2026`: `758`
  - `Nguồn dữ liệu tổng hợp 2026`: `38`

## Key business findings
- 2025 is now a **clean SpeakWell-only baseline**.
- 2026 is a **combined operating view** from 3 sources, so the UI must explicitly state this to avoid users assuming both years are sourced the same way.
- 2026 has materially higher unclassified share than 2025, so comparison sections should be read as operational comparison, not strict like-for-like accounting.

## UI/wording changes applied
Updated ticket comparison wording to make the scope explicit:
- tab label: `So sánh 2025 SpeakWell vs 2026`
- section title: `So sánh 2025 SpeakWell vs 2026 combined`
- year cards:
  - `2025 · SpeakWell`
  - `2026 · Combined`
- comparison note now explains:
  - same filter scope
  - ignore year/month/quarter filters in the comparison block
  - 2025 is SpeakWell baseline
  - 2026 combines multiple sources
- year cards now show source mix by year
- table headings and labels were Việt hóa / business-friendly:
  - `Nhóm lỗi`
  - `Chi tiết nhóm lỗi`
  - `So sánh nhóm lỗi`
  - `So sánh nguyên nhân gốc`
  - `So sánh pattern lỗi`
  - `Chênh issue`
  - `Chênh buổi bù`

## Files changed
- `src/services/analyticsService.js`
- `public/index.html`
