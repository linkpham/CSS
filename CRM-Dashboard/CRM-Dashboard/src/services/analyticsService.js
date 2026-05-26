// src/services/analyticsService.js

function normalizeMovementGroup(group = '') {
    const value = String(group || '').trim();
    if (value.startsWith('1.') || value.startsWith('2.')) return 'Phục hồi / cải thiện';
    if (value.startsWith('4.') || value.startsWith('5.')) return 'Trượt dốc';
    if (value.startsWith('3a.')) return 'Giữ nguyên tốt';
    if (value.startsWith('3b.') || value.startsWith('6.')) return 'Giữ nguyên xấu';
    if (value.startsWith('7.') || value.startsWith('8.') || value.startsWith('9.')) return 'Mới / không đủ base';
    return value || 'Không xác định';
}

function classifyHealthMovement(data) {
    return data.map(item => ({
        ...item,
        movement: {
            ...item.movement,
            normalized: normalizeMovementGroup(item.movement?.group),
        },
    }));
}

function parseDateValue(value) {
    if (!value) return null;
    const text = String(value).trim();
    const isoLike = /^\d{4}-\d{1,2}-\d{1,2}/.test(text) ? text : null;
    if (isoLike) return new Date(isoLike);
    const dmy = text.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
    if (dmy) return new Date(`${dmy[3]}-${dmy[2]}-${dmy[1]}`);
    const parsed = new Date(text);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function toMonthToken(value) {
    const parsed = parseDateValue(value);
    if (!parsed) return '';
    const year = parsed.getUTCFullYear();
    const month = String(parsed.getUTCMonth() + 1).padStart(2, '0');
    return `${year}-${month}`;
}

function normalizeDateRange(fromDate, toDate) {
    if (fromDate && toDate && fromDate > toDate) {
        return { fromDate: toDate, toDate: fromDate };
    }
    return { fromDate, toDate };
}

function deriveStudentStatus(item = {}) {
    const value = String(item?.renewal?.studentStatus || item?.studentStatus || item?.renewal?.lifecycleStatus || '').trim().toLowerCase();
    if (!value) return '';
    if (value.includes('expired') || value.includes('đã học hết buổi') || value.includes('hết gói') || value.includes('het goi')) return 'Expired';
    if (value.includes('onboarding') || value.includes('pending start') || value.includes('active') || value.includes('còn lịch') || value.includes('đang học')) return 'Active';
    return '';
}

function applyFilters(data, filters = {}) {
    const normalizedRange = normalizeDateRange(parseDateValue(filters.fromDate), parseDateValue(filters.toDate));
    const fromDate = normalizedRange.fromDate;
    const toDate = normalizedRange.toDate;
    const fromMonth = toMonthToken(fromDate);
    const toMonth = toMonthToken(toDate);
    return data.filter(item => {
        if (filters.quarter && String(item.period?.quarter || '') !== String(filters.quarter)) return false;
        if (filters.month && String(item.period?.month || '') !== String(filters.month)) return false;
        if (fromDate || toDate) {
            const itemMonth = String(item.period?.month || '').trim();
            if (itemMonth) {
                if (fromMonth && itemMonth < fromMonth) return false;
                if (toMonth && itemMonth > toMonth) return false;
            } else {
                const itemDate = parseDateValue(item.period?.date);
                if (!itemDate) return false;
                if (fromDate && itemDate < fromDate) return false;
                if (toDate) {
                    const inclusiveToDate = new Date(toDate.getTime());
                    inclusiveToDate.setUTCHours(23, 59, 59, 999);
                    if (itemDate > inclusiveToDate) return false;
                }
            }
        }
        if (filters.css && item.student.css !== filters.css) return false;
        if (filters.targetCategory && item.health.targetCategory !== filters.targetCategory) return false;
        if (filters.baseCategory && item.health.baseCategory !== filters.baseCategory) return false;
        if (filters.healthMovementGroup && item.movement.normalized !== filters.healthMovementGroup) return false;
        if (filters.group && item.movement.group !== filters.group) return false;
        if (filters.renewalStatus && item.renewal.status !== filters.renewalStatus) return false;
        if (filters.product && item.renewal.product !== filters.product) return false;
        if (filters.productType && item.renewal.productType !== filters.productType) return false;
        if (filters.teacherType && item.renewal.teacherType !== filters.teacherType) return false;
        if (filters.lifecycleStatus && item.renewal.lifecycleStatus !== filters.lifecycleStatus) return false;
        if (filters.studentStatus && deriveStudentStatus(item) !== filters.studentStatus) return false;
        if (filters.minScoreTarget !== undefined && filters.minScoreTarget !== '' && Number(item.health.scoreTarget) < Number(filters.minScoreTarget)) return false;
        if (filters.maxScoreTarget !== undefined && filters.maxScoreTarget !== '' && Number(item.health.scoreTarget) > Number(filters.maxScoreTarget)) return false;
        return true;
    });
}

function countBy(data, selector) {
    return data.reduce((acc, item) => {
        const key = selector(item) || 'Không xác định';
        acc[key] = (acc[key] || 0) + 1;
        return acc;
    }, {});
}

function sum(data, selector) {
    return data.reduce((total, item) => total + (Number(selector(item)) || 0), 0);
}

function avg(data, selector) {
    const values = data
        .map(selector)
        .map(value => Number(value))
        .filter(value => Number.isFinite(value));
    if (!values.length) return 0;
    return values.reduce((total, value) => total + value, 0) / values.length;
}

function pct(numerator, denominator) {
    if (!denominator) return '0.0%';
    return `${((numerator / denominator) * 100).toFixed(1)}%`;
}

function money(value) {
    return `${Math.round(Number(value) || 0).toLocaleString('vi-VN')}đ`;
}

function isRenewedRow(item) {
    return String(item?.renewal?.status || '').includes('Đã gia hạn');
}

function isNewSaleRow(item) {
    return String(item?.renewal?.status || '').includes('Bán mới');
}

function hasRealizedCash(item) {
    return isRenewedRow(item) || isNewSaleRow(item);
}

function getRenewedRows(data) {
    return data.filter(isRenewedRow);
}

function getRealizedCashRows(data) {
    return data.filter(hasRealizedCash);
}

function calculateComprehensiveMetrics(data) {
    const total = data.length;
    const renewedRows = getRenewedRows(data);
    const realizedCashRows = getRealizedCashRows(data);
    const renewalRevenue = sum(renewedRows, item => item.renewal.revenue);
    const realizedCashRevenue = sum(realizedCashRows, item => item.renewal.revenue);
    const avgRenewalRevenue = renewedRows.length
        ? renewalRevenue / renewedRows.length
        : (realizedCashRows.length ? realizedCashRevenue / realizedCashRows.length : 0);

    const movementCounts = countBy(data, item => item.movement.normalized);
    const detailedGroupCounts = countBy(data, item => item.movement.group);
    const targetHealthCounts = countBy(data, item => item.health.targetCategory);
    const baseHealthCounts = countBy(data, item => item.health.baseCategory);
    const renewalStatusCounts = countBy(data, item => item.renewal.status);
    const cssCounts = countBy(data, item => item.student.css || 'Chưa gán CSS');
    const lifecycleCounts = countBy(data, item => item.renewal.lifecycleStatus);

    const recovery = movementCounts['Phục hồi / cải thiện'] || 0;
    const slippage = movementCounts['Trượt dốc'] || 0;
    const stableGood = movementCounts['Giữ nguyên tốt'] || 0;
    const stableBad = movementCounts['Giữ nguyên xấu'] || 0;
    const missingBase = movementCounts['Mới / không đủ base'] || 0;

    const realizedByHealth = Object.fromEntries(Object.keys(targetHealthCounts).map(category => {
        const rows = data.filter(item => item.health.targetCategory === category);
        const renewed = rows.filter(isRenewedRow).length;
        return [category, { total: rows.length, renewed }];
    }));

    const forecastByHealth = Object.entries(targetHealthCounts).map(([category, count]) => {
        const realized = realizedByHealth[category] || { total: count, renewed: 0 };
        let defaultRr = 0.15;
        if (category.includes('Khỏe mạnh')) defaultRr = 0.35;
        else if (category.includes('Cần chú ý')) defaultRr = 0.22;
        else if (category.includes('Báo động')) defaultRr = 0.10;
        const realizedRate = realized.total ? (realized.renewed / realized.total) : 0;
        const appliedRate = Math.max(defaultRr, realizedRate);
        const eligibleCount = Math.max(count - realized.renewed, 0);
        return {
            category,
            students: count,
            realizedRenewed: realized.renewed,
            eligibleStudents: eligibleCount,
            defaultRenewalRate: appliedRate,
            forecastRevenue: eligibleCount * appliedRate * (avgRenewalRevenue || 5000000),
        };
    });

    const forecastRevenue = sum(forecastByHealth, item => item.forecastRevenue);

    return {
        overview: {
            totalStudents: total,
            avgScoreTarget: avg(data, item => item.health.scoreTarget).toFixed(1),
            avgScoreBase: avg(data, item => item.health.scoreBase).toFixed(1),
            recoveryRate: pct(recovery, total),
            slippageRate: pct(slippage, total),
            stableRate: pct(stableGood + stableBad, total),
            renewalRate: pct(renewedRows.length, total),
            cashRevenue: money(realizedCashRevenue),
            forecastRevenue: money(forecastRevenue),
        },
        healthMovement: {
            recovery,
            slippage,
            stableGood,
            stableBad,
            missingBase,
            counts: movementCounts,
            detailedCounts: detailedGroupCounts,
            targetHealthCounts,
            baseHealthCounts,
        },
        careEffectiveness: {
            cssCounts,
            avgUnfinishedRate: `${(avg(data, item => item.operation.unfinishedRate) * 100).toFixed(1)}%`,
            avgTeacherDisruptionRate: `${(avg(data, item => item.operation.teacherDisruptionRate) * 100).toFixed(1)}%`,
        },
        renewalCorrelation: {
            renewalStatusCounts,
            renewalRateByHealth: Object.fromEntries(Object.keys(targetHealthCounts).map(category => {
                const rows = data.filter(item => item.health.targetCategory === category);
                return [category, pct(getRenewedRows(rows).length, rows.length)];
            })),
            lifecycleCounts,
        },
        forecast: {
            avgRenewalRevenue: money(avgRenewalRevenue),
            forecastRevenue: money(forecastRevenue),
            byHealth: forecastByHealth.map(item => ({
                ...item,
                defaultRenewalRate: `${(item.defaultRenewalRate * 100).toFixed(0)}%`,
                forecastRevenue: money(item.forecastRevenue),
            })),
            cashRevenueRealized: money(realizedCashRevenue),
            realizedCashCount: realizedCashRows.length,
            renewedCount: renewedRows.length,
            newSaleCount: realizedCashRows.filter(isNewSaleRow).length,
        },
    };
}

function getFilterOptions(data) {
    const unique = selector => [...new Set(data.map(selector).filter(Boolean))].sort((a, b) => String(a).localeCompare(String(b), 'vi'));
    return {
        quarter: unique(item => item.period?.quarter),
        month: unique(item => item.period?.month),
        healthMovementGroup: unique(item => item.movement?.normalized),
        css: unique(item => item.student.css),
        targetCategory: unique(item => item.health.targetCategory),
        baseCategory: unique(item => item.health.baseCategory),
        group: unique(item => item.movement.group),
        renewalStatus: unique(item => item.renewal.status),
        product: unique(item => item.renewal.product),
        productType: unique(item => item.renewal.productType),
        teacherType: unique(item => item.renewal.teacherType),
        lifecycleStatus: unique(item => item.renewal.lifecycleStatus),
        studentStatus: unique(item => deriveStudentStatus(item)),
    };
}

module.exports = {
    classifyHealthMovement,
    applyFilters,
    calculateComprehensiveMetrics,
    getFilterOptions,
    normalizeMovementGroup,
    parseDateValue,
};
