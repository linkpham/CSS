# Ticket data source update

Date: 2026-05-25
Module: `https://crm.icanwork.vn/ticket/`

## Requested update delivered
### 2025 source
Replaced the 2025 comparison source with Google Sheet:
- `1Y2rlucL3ttaawNFYHxtUlO605MY_k3ZPckRiJSDb4IU`

Applied rules:
- only include sheets matching `2025`
- exclude non-data sheets like tổng hợp / cú pháp / bảng tổng hợp / trang tính / phân loại
- only keep rows whose product matches `Speakwell`, `SpeakWell`, or `Speak Well`

### 2026 source
Added an additional 2026 source with Google Sheet:
- `1ahUT6qHDIIwQXWr5hNTJ9M22VdqOfOA5sUmFOiWGuJ0`

Applied rules:
- include only `2026` sheets
- exclude `test` and non-data helper sheets
- combine with the existing 2026 sources already used by ticket dashboard

### Existing 2026 sources retained
- `1m5DpCr8I9sAjeyoYkGudEjYuk4f9zigbPyUx9efVwWk`
- `1wrevnMdOAz7jDqONNesRU-iIBj49dWs0KDAotEZVC_M` (now only `2026 - Bù thực tế`)

## Code changes
### Source config
Updated:
- `src/config/sourceConfig.js`

### Google Sheet loader
Added support for:
- `includeSheetPatterns`
- `excludeSheetPatterns`
- `productPatterns`

Updated:
- `src/services/gsheetService.js`

### Sync loop stabilization
To reduce immediate post-deploy quota collisions:
- added `SYNC_INITIAL_DELAY_MS`
- `scripts/sync-loop.sh`
- `docker-compose.yml`

## Verified local sync result
Local isolated sync result:
- total rows: `4740`
- by source/year:
  - `feedback-2025-speakwell | 2025 = 2093`
  - `feedback-2026 | 2026 = 758`
  - `feedback-consolidated-2026 | 2026 = 38`
  - `feedback-2026-bos | 2026 = 1851`
- by year:
  - `2025 = 2093`
  - `2026 = 2647`

## Production verification
After deploy + manual sync:
- dashboard rowCount: `4740`
- year comparison totals:
  - `2025 = 2093`
  - `2026 = 2647`
- source labels visible in filter options:
  - `Nguồn dữ liệu 2025 - SpeakWell`
  - `Nguồn dữ liệu 2026`
  - `Nguồn dữ liệu tổng hợp 2026`
  - `Nguồn bổ sung 2026 - BOS`
- latest sync status verified back to `completed`
