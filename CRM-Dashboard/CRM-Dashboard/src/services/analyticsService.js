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

function applyFilters(data, filters = {}) {
    const fromDate = parseDateValue(filters.fromDate);
    const toDate = parseDateValue(filters.toDate);
    return data.filter(item => {
        if (filters.quarter && String(item.period?.quarter || '') !== String(filters.quarter)) return false;
        if (filters.month && String(item.period?.month || '') !== String(filters.month)) return false;
        if ((fromDate || toDate) && item.period?.date) {
            const itemDate = parseDateValue(item.period.date);
            if (itemDate) {
                if (fromDate && itemDate < fromDate) return false;
                if (toDate && itemDate > toDate) return false;
            }
        }
        if (filters.css && item.student.css !== filters.css) return false;
        if (filters.targetCategory && item.health.targetCategory !== filters.targetCategory) return false;
        if (filters.baseCategory && item.health.baseCategory !== filters.baseCategory) return false;
        if (filters.healthMovementGroup && item.movement.normalized !== filters.healthMovementGroup) return false;
        if (filters.group && item.movement.group !== filters.group) return false;
        if (filters.renewalStatus && item.renewal.status !== filters.renewalStatus) return false;
        if (filters.product && item.renewal.product !== filters.product) return false;
        if (filters.lifecycleStatus && item.renewal.lifecycleStatus !== filters.lifecycleStatus) return false;
        if (filters.minScoreTarget !== undefined && filters.minScoreTarget !== '' && item.health.scoreTarget < Number(filters.minScoreTarget)) return false;
        if (filters.maxScoreTarget !== undefined && filters.maxScoreTarget !== '' && item.health.scoreTarget > Number(filters.maxScoreTarget)) return false;
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
    if (!data.length) return 0;
    return sum(data, selector) / data.length;
}

function pct(numerator, denominator) {
    if (!denominator) return '0.0%';
    return `${((numerator / denominator) * 100).toFixed(1)}%`;
}

function money(value) {
    return `${Math.round(Number(value) || 0).toLocaleString('vi-VN')}đ`;
}

function getRenewedRows(data) {
    return data.filter(item => item.renewal.status && !item.renewal.status.includes('Chưa gia hạn'));
}

function calculateComprehensiveMetrics(data) {
    const total = data.length;
    const renewedRows = getRenewedRows(data);
    const renewalRevenue = sum(data, item => item.renewal.revenue);
    const avgRenewalRevenue = renewedRows.length ? renewalRevenue / renewedRows.length : 0;

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

    const forecastByHealth = Object.entries(targetHealthCounts).map(([category, count]) => {
        let defaultRr = 0.15;
        if (category.includes('Khỏe mạnh')) defaultRr = 0.35;
        else if (category.includes('Cần chú ý')) defaultRr = 0.22;
        else if (category.includes('Báo động')) defaultRr = 0.10;
        return {
            category,
            students: count,
            defaultRenewalRate: defaultRr,
            forecastRevenue: count * defaultRr * (avgRenewalRevenue || 5000000),
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
            cashRevenue: money(renewalRevenue),
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
        lifecycleStatus: unique(item => item.renewal.lifecycleStatus),
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
