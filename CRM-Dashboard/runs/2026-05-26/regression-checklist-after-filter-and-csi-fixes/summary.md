# Regression checklist after filter and CSI fixes

Date: 2026-05-26
Environment: production app inside `icc-crm-app`

## Checklist

### 1. CRM month filter vs same-month date range
- `GET /api/students?month=2026-05`
- `GET /api/students?fromDate=2026-05-01&toDate=2026-05-31`

Result:
- `month_total = 4932`
- `range_total = 4932`
- `equal = true`

### 2. CRM multi-month date range target/base binding
- `GET /api/students?fromDate=2026-01-01&toDate=2026-05-31&search=1746`

Sample result:
- learner `1746` → `target=2026-05`, `base=2026-04`
- learner `3080` → `target=2026-03`, `base=2026-02`
- learner `3940` → `target=2026-02`, `base=2026-01`

Conclusion:
- target is the latest snapshot within range for each learner
- base is the nearest prior month

### 3. CRM studentStatus filter
- `GET /api/students?month=2026-05&studentStatus=Active`
- `GET /api/students?month=2026-05&studentStatus=Expired`

Result:
- `Active = 4653`
- `Expired = 277`
- samples returned correct labels only

### 4. Dashboard compare basis for multi-month range
- `GET /api/dashboard?fromDate=2026-03-01&toDate=2026-05-31`

Result:
- `defaultTargetMonth = 2026-04`
- `activeTargetMonth = ''`
- `activeTargetRange = { fromMonth: '2026-03', toMonth: '2026-05', mode: 'range' }`

Conclusion:
- payload now distinguishes default CRM basis vs active range basis

### 5. CSI role scope
- Head `GET /api/csi/health-dashboard?month=2026-05`
- Staff `anhptl` same request

Result:
- `Head = 4850`
- `Staff = 552`

Conclusion:
- CSI live now respects backend role scope

### 6. CSI studentStatus filter
- Staff `GET /api/csi/health-dashboard?month=2026-05&studentStatus=Active`
- Staff `GET /api/csi/health-dashboard?month=2026-05&studentStatus=Expired`

Result:
- `Active = 521`
- `Expired = 31`

Conclusion:
- CSI live now supports Active / Expired filtering

### 7. CSI month filter vs same-month date range
- `GET /api/csi/health-dashboard?month=2026-05`
- `GET /api/csi/health-dashboard?fromDate=2026-05-01&toDate=2026-05-31`

Result:
- `month_total = 4850`
- `range_total = 4850`
- `equal = true`

## Overall status
Regression checklist passed for the main filters fixed in this batch:
- CRM month filter
- CRM multi-month date range
- CRM Active / Expired
- CSI backend role scope
- CSI Active / Expired
- CSI time-filter consistency
