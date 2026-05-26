const express = require('express');
const http = require('http');
const path = require('path');
const { Server } = require('socket.io');
const db = require('./db/database');
const {
    classifyHealthMovement,
    applyFilters,
    calculateComprehensiveMetrics,
    getFilterOptions,
} = require('./services/analyticsService');
const {
    getZeusDbConnectionDefaults,
    testZeusDbConnection,
} = require('./services/dbConnectionService');
const {
    getCsiStudents,
    getCsiSummary,
    getCsiHealthDistribution,
    getCsiScoreDistribution,
    getCsiCssPerformance,
    getCsiTeacherWarning,
    getCsiHealthDashboard,
} = require('./services/csiService');
const {
    ROLE,
    bootstrapHeadUser,
    login,
    logout,
    getUserByToken,
    listUsers,
    createUser,
    updateUser,
    changeOwnPassword,
    resetUserPassword,
    listAuditLogs,
    getAllowedCssScopes,
} = require('./auth/authService');
const {
    listLearnerJourneyStudents,
    listLearnerJourneyPurchaseHistory,
} = require('./services/learnerJourneyService');
const {
    getStudentLcmsStatsBatch,
    getStudentLcmsDetailByUserId,
} = require('./services/lcmsService');
const { requireAuth, requireAnyRole } = require('./auth/authMiddleware');

const app = express();
app.use(express.json());
app.use(express.static(path.join(__dirname, '../public')));

const server = http.createServer(app);
const io = new Server(server, {
    cors: { origin: '*', methods: ['GET', 'POST', 'PATCH'] },
});

function dbAll(sql, params = []) {
    return new Promise((resolve, reject) => {
        db.all(sql, params, (err, rows) => {
            if (err) reject(err);
            else resolve(rows);
        });
    });
}

const DATA_CACHE_TTL_MS = Number(process.env.DATA_CACHE_TTL_MS || 15000);
const CSI_SNAPSHOT_CACHE_TTL_MS = Number(process.env.CSI_SNAPSHOT_CACHE_TTL_MS || 30 * 60 * 1000);
const LCMS_BATCH_CACHE_TTL_MS = Number(process.env.LCMS_BATCH_CACHE_TTL_MS || 30 * 60 * 1000);
const LCMS_DETAIL_CACHE_TTL_MS = Number(process.env.LCMS_DETAIL_CACHE_TTL_MS || 15 * 60 * 1000);
let cachedDashboardData = null;
let cachedDashboardFilterOptions = null;
let cachedDashboardLastSyncedAt = null;
let cachedDashboardAt = 0;
let cachedCsiLegacySnapshot = null;
let cachedCsiLegacySnapshotAt = 0;
let cachedUnifiedData = null;
let cachedUnifiedFilterOptions = null;
let cachedUnifiedLastSyncedAt = null;
let cachedUnifiedAt = 0;
let cachedLcmsBatchStats = null;
let cachedLcmsBatchStatsAt = 0;
let cachedLcmsBatchStatsKey = '';
const cachedLcmsDetails = new Map();

function mapDbRow(row) {
    return {
        period: {
            date: row.cut_off_date || '',
            month: row.period_month || '',
            quarter: row.period_quarter || '',
            year: row.period_year || '',
            week: row.period_week || '',
        },
        student: {
            id: row.student_id,
            name: row.student_name,
            email: row.email,
            phone: row.phone,
            css: row.css,
        },
        health: {
            scoreTarget: Number(row.score_target) || 0,
            scoreBase: Number(row.score_base) || 0,
            variance: Number(row.variance) || 0,
            targetCategory: row.target_category || '',
            baseCategory: row.base_category || '',
            managementScore: Number(row.management_health_score) || 0,
            learningPace: Number(row.learning_pace) || 0,
        },
        movement: {
            group: row.movement_group || '',
        },
        operation: {
            teacherDisruptionRate: Number(row.teacher_disruption_rate) || 0,
            unfinishedRate: Number(row.unfinished_rate) || 0,
            activationSpeed: row.activation_speed || '',
            teacherDisruptionCumulative: Number(row.teacher_disruption_cumulative) || 0,
        },
        renewal: {
            status: row.renewal_status || '',
            revenue: Number(row.renewal_revenue) || 0,
            product: row.renewal_product || '',
            remainingSessions: Number(row.remaining_sessions) || 0,
            lifecycleStatus: row.lifecycle_status || '',
        },
        syncedAt: row.synced_at,
    };
}

function serializeStudentRow(item, learnerRow = null) {
    const resolvedLearnerRow = learnerRow || item.journey || null;
    const base = {
        id: item.student.id,
        name: item.student.name,
        email: item.student.email,
        phone: item.student.phone,
        css: item.student.css,
        cutOffDate: item.period.date,
        month: item.period.month,
        quarter: item.period.quarter,
        year: item.period.year,
        week: item.period.week,
        scoreTarget: item.health.scoreTarget,
        scoreBase: item.health.scoreBase,
        variance: item.health.variance,
        targetCategory: item.health.targetCategory,
        baseCategory: item.health.baseCategory,
        managementScore: item.health.managementScore,
        learningPace: item.health.learningPace,
        movementGroup: item.movement.group,
        movementNormalized: item.movement.normalized,
        teacherDisruptionRate: item.operation.teacherDisruptionRate,
        unfinishedRate: item.operation.unfinishedRate,
        activationSpeed: item.operation.activationSpeed,
        teacherDisruptionCumulative: item.operation.teacherDisruptionCumulative,
        renewalStatus: item.renewal.status,
        renewalRevenue: item.renewal.revenue,
        renewalProduct: item.renewal.product,
        productType: item.renewal.productType || '',
        teacherType: item.renewal.teacherType || '',
        remainingSessions: item.renewal.remainingSessions,
        lifecycleStatus: item.renewal.lifecycleStatus,
        latestOrderAmount: 0,
        latestOrderDate: '',
        csiHealthCategory: item.csi?.healthCategory || '',
        csiTeacherWarning: item.csi?.teacherWarning || '',
        csiTotalScheduled: item.csi?.totalScheduled || 0,
        csiTotalSuccess: item.csi?.totalSuccess || 0,
        csiSuccessRate: item.csi?.successRate,
        lcmsHomeworkCompletionRate: item.lcms?.hwCompletionRate,
        lcmsHomeworkAvgScore: item.lcms?.hwAvgScore,
        lcmsTestAvgScore: item.lcms?.testAvgScore,
        lcmsHasRealData: [item.lcms?.hwCompletionRate, item.lcms?.hwAvgScore, item.lcms?.testAvgScore].some(value => value !== null && value !== undefined),
        studentStatus: item.renewal?.studentStatus || deriveStudentStatus(item.renewal?.lifecycleStatus),
        targetSnapshotMonth: item.csi?.targetMonth || item.period.month || '',
        baseSnapshotMonth: item.csi?.baseMonth || '',
        sourceCoverage: item.source || {},
    };
    if (!resolvedLearnerRow) return base;
    return {
        ...base,
        classSizes: resolvedLearnerRow.class_sizes || '',
        packageNames: resolvedLearnerRow.package_names || '',
        packageGroups: resolvedLearnerRow.package_groups || '',
        teacherType: normalizeTeacherTypeValue(resolvedLearnerRow.teacher_types || base.teacherType || ''),
        purchasedSessions: Number(resolvedLearnerRow.purchased_sessions) || 0,
        unscheduledSessions: Number(resolvedLearnerRow.unscheduled_sessions) || 0,
        scheduledSessions: Number(resolvedLearnerRow.scheduled_sessions) || 0,
        completedSessions: Number(resolvedLearnerRow.completed_sessions) || 0,
        cancelledSessions: Number(resolvedLearnerRow.cancelled_sessions) || 0,
        remainingSessions: Number(resolvedLearnerRow.remaining_sessions) || base.remainingSessions || 0,
        journeyStatus: resolvedLearnerRow.journey_status || base.lifecycleStatus,
        studentStatus: base.studentStatus || deriveStudentStatus(resolvedLearnerRow.journey_status || base.lifecycleStatus || ''),
        firstLessonStarttime: resolvedLearnerRow.first_lesson_starttime || '',
        lastLessonStarttime: resolvedLearnerRow.last_lesson_starttime || '',
        latestOrderAmount: Number(resolvedLearnerRow.latest_order_amount) || 0,
        latestOrderDate: resolvedLearnerRow.latest_order_date || '',
        renewalProduct: resolvedLearnerRow.package_names || base.renewalProduct,
        lifecycleStatus: resolvedLearnerRow.journey_status || base.lifecycleStatus,
    };
}

function applyStudentSearch(data, search = '') {
    const keyword = String(search || '').trim().toLowerCase();
    if (!keyword) return data;
    return data.filter(item => {
        const text = [
            item.student.id,
            item.student.name,
            item.student.email,
            item.student.phone,
            item.student.css,
            item.health.targetCategory,
            item.health.baseCategory,
            item.movement.group,
            item.movement.normalized,
            item.renewal.status,
            item.renewal.product,
            item.renewal.lifecycleStatus,
            item.csi?.healthCategory,
            item.csi?.teacherWarning,
            item.csi?.courseNames,
            item.journey?.package_names,
            item.journey?.package_groups,
        ].join(' ').toLowerCase();
        return text.includes(keyword);
    });
}

function buildDashboardStudentIndex(data = []) {
    const map = new Map();
    data.forEach(item => {
        map.set(String(item.student.id), serializeStudentRow(item));
    });
    return map;
}

function toFiniteNumberOrNull(value) {
    const number = Number(value);
    return Number.isFinite(number) ? number : null;
}

function firstFiniteNumber(...values) {
    for (const value of values) {
        const number = toFiniteNumberOrNull(value);
        if (number !== null) return number;
    }
    return null;
}

function firstNonEmptyValue(...values) {
    for (const value of values) {
        if (value === null || value === undefined) continue;
        if (typeof value === 'number') return value;
        const text = String(value).trim();
        if (text) return value;
    }
    return '';
}

function mapCsiCategoryToTargetCategory(category = '') {
    const value = String(category || '').toLowerCase();
    if (value.includes('xanh') || value.includes('khỏe mạnh') || value.includes('khoe manh')) return '3. Khỏe mạnh (85-100)';
    if (value.includes('vàng') || value.includes('cảnh báo') || value.includes('canh bao')) return '2. Cần chú ý (60-84)';
    if (value.includes('đỏ') || value.includes('do') || value.includes('báo động') || value.includes('bao dong')) return '1. Báo động (<60)';
    return '';
}

const JOURNEY_STATUS_LABELS = {
    Onboarding: '1. Onboarding - chưa học buổi đầu',
    'Pending start': '1b. Onboarding - có buổi nhưng chưa bắt đầu',
    Active: '2. Active - còn buổi đang học',
    'Scheduled only': '3. Hết buổi nhưng còn lịch',
    Expired: '4. Expired - đã học hết buổi',
    Unknown: 'Chưa rõ hành trình',
};

function normalizeJourneyStatusLabel(value = '') {
    const text = String(value || '').trim();
    return JOURNEY_STATUS_LABELS[text] || text;
}

function deriveStudentStatus(value = '') {
    const text = String(value || '').trim().toLowerCase();
    if (!text) return '';
    if (text.includes('expired') || text.includes('đã học hết buổi') || text.includes('het goi') || text.includes('hết gói')) return 'Expired';
    if (
        text.includes('onboarding')
        || text.includes('pending start')
        || text.includes('active')
        || text.includes('còn lịch')
        || text.includes('đang học')
    ) {
        return 'Active';
    }
    return '';
}

function splitPipeValues(value = '') {
    return String(value || '')
        .split('|')
        .map(item => item.trim())
        .filter(Boolean);
}

function buildPeriodMetaFromDateValue(value = '') {
    const text = String(value || '').trim();
    if (!text) return null;
    const normalized = text.includes('T') ? text : text.replace(' ', 'T');
    const parsed = new Date(normalized);
    if (Number.isNaN(parsed.getTime())) return null;
    return buildMonthMeta(new Date(Date.UTC(parsed.getUTCFullYear(), parsed.getUTCMonth(), 1)));
}

function deriveFallbackPeriodMeta(learnerRow = null) {
    return buildPeriodMetaFromDateValue(learnerRow?.latest_order_date)
        || buildPeriodMetaFromDateValue(learnerRow?.first_lesson_starttime)
        || buildPeriodMetaFromDateValue(learnerRow?.last_lesson_starttime);
}

function finalizeTeacherTypes(types = []) {
    const normalized = [...new Set(types.map(value => String(value || '').trim()).filter(Boolean).map(value => {
        if (value === 'GV Native 1' || value === 'GV Native 2') return 'GV Native';
        return value;
    }))];
    if (!normalized.length) return '';
    return normalized.length === 1 ? normalized[0] : 'Mixed teacher types';
}

function normalizeTeacherTypeValue(value = '') {
    const text = String(value || '').trim();
    if (!text) return '';
    if (text.toLowerCase().includes('mixed teacher types')) return 'Mixed teacher types';
    const directParts = text.split('|').map(part => part.trim()).filter(Boolean);
    if (directParts.some(part => part.startsWith('GV '))) {
        return finalizeTeacherTypes(directParts);
    }
    return deriveTeacherType(text);
}

function deriveTeacherType(packageText = '') {
    const text = String(packageText || '').toLowerCase();
    const matches = [];
    if (text.includes('việt nam') || text.includes('viet nam')) matches.push('GV Việt Nam');
    if (text.includes('philippines')) matches.push('GV Philippines');
    if (text.includes('native 1') || text.includes('native 2') || text.includes('nam phi') || text.includes('uk') || text.includes('american')) matches.push('GV Native');
    return finalizeTeacherTypes(matches);
}

function deriveProductType(learnerRow = null, legacyRenewalProduct = '') {
    const packageGroups = splitPipeValues(learnerRow?.package_groups);
    if (packageGroups.length) return packageGroups[0];
    const packageNames = splitPipeValues(learnerRow?.package_names);
    if (packageNames.length) return packageNames[0];
    return splitPipeValues(legacyRenewalProduct)[0] || '';
}

function deriveJourneyRenewalStatus(learnerRow = null) {
    if (!learnerRow) return '';
    const remainingSessions = Number(learnerRow.remaining_sessions) || 0;
    const purchasedSessions = Number(learnerRow.purchased_sessions) || 0;
    if (remainingSessions > 0) return 'Còn buổi';
    if (purchasedSessions > 0) return '⏳ Chưa gia hạn';
    return learnerRow.journey_status || '';
}

function calculatePeriodWeekSpan(fromDate, toDate) {
    const start = Date.parse(fromDate || '');
    const end = Date.parse(toDate || '');
    if (!Number.isFinite(start) || !Number.isFinite(end) || end <= start) return 1;
    return Math.max((end - start) / (7 * 24 * 60 * 60 * 1000), 1);
}

function buildCsiStudentIndex(rows = []) {
    const map = new Map();
    rows.forEach(row => {
        map.set(String(row.student_id), row);
    });
    return map;
}

function buildLegacyStudentItemIndex(data = []) {
    const map = new Map();
    data.forEach(item => {
        map.set(String(item.student.id), item);
    });
    return map;
}

function getPurchaseStatsForMonth(purchaseStats = null, targetMonthLabel = '') {
    if (!purchaseStats || !targetMonthLabel) {
        return {
            targetMonthOrderCount: 0,
            targetMonthOrderAmount: 0,
            hasPriorPaidOrderBeforeTargetMonth: false,
        };
    }
    const monthStats = purchaseStats.monthlyPaidOrders?.[targetMonthLabel] || { count: 0, amount: 0 };
    const hasPriorPaidOrderBeforeTargetMonth = Object.keys(purchaseStats.monthlyPaidOrders || {}).some(monthLabel => monthLabel < targetMonthLabel && (purchaseStats.monthlyPaidOrders?.[monthLabel]?.count || 0) > 0);
    return {
        targetMonthOrderCount: monthStats.count || 0,
        targetMonthOrderAmount: monthStats.amount || 0,
        hasPriorPaidOrderBeforeTargetMonth,
    };
}

function buildUnifiedDashboardItem(dashboardItem = null, learnerRow = null, csiSnapshot = null, purchaseStats = null, lcmsStats = null) {
    const legacyPeriod = dashboardItem?.period || {};
    const legacyStudent = dashboardItem?.student || {};
    const legacyHealth = dashboardItem?.health || {};
    const legacyMovement = dashboardItem?.movement || {};
    const legacyOperation = dashboardItem?.operation || {};
    const legacyRenewal = dashboardItem?.renewal || {};
    const snapshotHealth = csiSnapshot?.health || {};
    const snapshotOperation = csiSnapshot?.operation || {};
    const snapshotCsi = csiSnapshot?.csi || {};
    const fallbackPeriodMeta = deriveFallbackPeriodMeta(learnerRow);
    const normalizedJourneyStatus = normalizeJourneyStatusLabel(learnerRow?.journey_status);
    const resolvedPackageNames = firstNonEmptyValue(learnerRow?.package_names, legacyRenewal.product, '');
    const resolvedProductType = deriveProductType(learnerRow, legacyRenewal.product);
    const resolvedTeacherType = firstNonEmptyValue(normalizeTeacherTypeValue(learnerRow?.teacher_types), deriveTeacherType(resolvedPackageNames));
    const resolvedPeriod = {
        date: csiSnapshot?.period?.date || legacyPeriod.date || fallbackPeriodMeta?.toDate || '',
        month: csiSnapshot?.period?.month || legacyPeriod.month || fallbackPeriodMeta?.periodMonth || '',
        quarter: csiSnapshot?.period?.quarter || legacyPeriod.quarter || fallbackPeriodMeta?.periodQuarter || '',
        year: csiSnapshot?.period?.year || legacyPeriod.year || fallbackPeriodMeta?.periodYear || '',
        week: csiSnapshot?.period?.week || legacyPeriod.week || fallbackPeriodMeta?.periodWeek || '',
    };
    const snapshotBaseCategory = firstNonEmptyValue(snapshotHealth.baseCategory, legacyHealth.baseCategory);
    const heuristicsIsNew = snapshotBaseCategory === 'Mới';
    const heuristicRenewalStatus = heuristicsIsNew ? 'Bán mới' : '⏳ Chưa gia hạn';
    const heuristicRenewalProduct = heuristicsIsNew ? 'Onboarding' : '';
    const heuristicLifecycleStatus = heuristicsIsNew ? '1. Mới (Onboarding)' : '4. Hết gói (Gap chờ phí)';
    const resolvedStudentStatus = deriveStudentStatus(firstNonEmptyValue(normalizedJourneyStatus, legacyRenewal.lifecycleStatus, heuristicLifecycleStatus));
    const remainingSessions = firstFiniteNumber(learnerRow?.remaining_sessions, legacyRenewal.remainingSessions) ?? 0;
    const monthScopedPurchaseStats = getPurchaseStatsForMonth(purchaseStats, resolvedPeriod.month);
    const estimatedRenewalStatus = monthScopedPurchaseStats.targetMonthOrderCount
        ? (monthScopedPurchaseStats.hasPriorPaidOrderBeforeTargetMonth ? '✅ Đã gia hạn' : 'Bán mới')
        : (remainingSessions > 0 ? '⏳ Chưa đến hạn' : (heuristicsIsNew ? 'Bán mới' : '⏳ Chưa gia hạn'));
    const estimatedRenewalRevenue = monthScopedPurchaseStats.targetMonthOrderAmount || 0;
    const estimatedActivationSpeed = estimatedRenewalStatus === 'Bán mới' ? 'Thiếu ngày' : '';
    const shouldTrustModernActivation = Boolean(learnerRow || purchaseStats || csiSnapshot);

    return {
        period: resolvedPeriod,
        student: {
            id: firstNonEmptyValue(learnerRow?.student_id, csiSnapshot?.studentId, legacyStudent.id),
            name: firstNonEmptyValue(learnerRow?.student_name, csiSnapshot?.targetRow?.student_name, legacyStudent.name),
            email: firstNonEmptyValue(learnerRow?.email, csiSnapshot?.targetRow?.email, legacyStudent.email),
            phone: firstNonEmptyValue(learnerRow?.phone, csiSnapshot?.targetRow?.phone, legacyStudent.phone),
            css: firstNonEmptyValue(learnerRow?.css, csiSnapshot?.targetRow?.css_staff, legacyStudent.css),
        },
        health: {
            scoreTarget: firstFiniteNumber(snapshotHealth.scoreTarget, legacyHealth.scoreTarget),
            scoreBase: firstFiniteNumber(snapshotHealth.scoreBase, legacyHealth.scoreBase),
            variance: firstFiniteNumber(snapshotHealth.variance, legacyHealth.variance),
            targetCategory: firstNonEmptyValue(snapshotHealth.targetCategory, legacyHealth.targetCategory, mapCsiCategoryToTargetCategory(snapshotCsi.healthCategory || '')),
            baseCategory: firstNonEmptyValue(snapshotHealth.baseCategory, legacyHealth.baseCategory, csiSnapshot ? 'Mới' : ''),
            managementScore: firstFiniteNumber(snapshotHealth.managementScore, legacyHealth.managementScore),
            learningPace: firstFiniteNumber(snapshotHealth.learningPace, legacyHealth.learningPace),
        },
        movement: {
            group: firstNonEmptyValue(csiSnapshot?.movementGroup, legacyMovement.group, (csiSnapshot || learnerRow) ? '7. Mới từ Zeus (chưa có base)' : ''),
        },
        operation: {
            teacherDisruptionRate: firstFiniteNumber(snapshotOperation.teacherDisruptionRate, legacyOperation.teacherDisruptionRate),
            unfinishedRate: firstFiniteNumber(snapshotOperation.unfinishedRate, legacyOperation.unfinishedRate),
            activationSpeed: shouldTrustModernActivation ? estimatedActivationSpeed : firstNonEmptyValue(legacyOperation.activationSpeed),
            teacherDisruptionCumulative: firstFiniteNumber(snapshotOperation.teacherDisruptionCumulative, legacyOperation.teacherDisruptionCumulative),
        },
        renewal: {
            status: firstNonEmptyValue(estimatedRenewalStatus, legacyRenewal.status, heuristicRenewalStatus, deriveJourneyRenewalStatus(learnerRow)),
            revenue: Number(estimatedRenewalRevenue || 0) > 0
                ? Number(estimatedRenewalRevenue || 0)
                : (firstFiniteNumber(legacyRenewal.revenue) ?? 0),
            product: firstNonEmptyValue(resolvedPackageNames, legacyRenewal.product, heuristicRenewalProduct),
            productType: firstNonEmptyValue(resolvedProductType),
            teacherType: firstNonEmptyValue(resolvedTeacherType),
            remainingSessions,
            lifecycleStatus: firstNonEmptyValue(normalizedJourneyStatus, legacyRenewal.lifecycleStatus, heuristicLifecycleStatus),
            studentStatus: resolvedStudentStatus,
            estimatedStatus: estimatedRenewalStatus,
            estimatedRevenue: estimatedRenewalRevenue,
        },
        journey: learnerRow ? {
            student_id: learnerRow.student_id,
            class_sizes: learnerRow.class_sizes || '',
            package_names: learnerRow.package_names || '',
            package_groups: learnerRow.package_groups || '',
            teacher_types: learnerRow.teacher_types || '',
            purchased_sessions: Number(learnerRow.purchased_sessions) || 0,
            unscheduled_sessions: Number(learnerRow.unscheduled_sessions) || 0,
            scheduled_sessions: Number(learnerRow.scheduled_sessions) || 0,
            completed_sessions: Number(learnerRow.completed_sessions) || 0,
            cancelled_sessions: Number(learnerRow.cancelled_sessions) || 0,
            remaining_sessions: Number(learnerRow.remaining_sessions) || 0,
            journey_status: normalizedJourneyStatus || '',
            first_lesson_starttime: learnerRow.first_lesson_starttime || '',
            last_lesson_starttime: learnerRow.last_lesson_starttime || '',
            latest_order_amount: Number(learnerRow.latest_order_amount) || 0,
            latest_order_date: learnerRow.latest_order_date || '',
            synced_at: learnerRow.synced_at || '',
        } : null,
        csi: {
            healthCategory: snapshotCsi.healthCategory || '',
            teacherWarning: snapshotCsi.teacherWarning || '',
            totalScheduled: Number(snapshotCsi.totalScheduled) || 0,
            totalSuccess: Number(snapshotCsi.totalSuccess) || 0,
            studentNoshow: Number(snapshotCsi.studentNoshow) || 0,
            studentHalf: Number(snapshotCsi.studentHalf) || 0,
            teacherNoshow: Number(snapshotCsi.teacherNoshow) || 0,
            successRate: firstFiniteNumber(snapshotCsi.successRate),
            avgPerWeek: firstFiniteNumber(snapshotCsi.avgPerWeek),
            ontrackScore: firstFiniteNumber(snapshotCsi.ontrackScore),
            courseNames: snapshotCsi.courseNames || '',
            targetMonth: csiSnapshot?.targetMonth?.label || '',
            baseMonth: csiSnapshot?.baseMonth?.label || '',
        },
        lcms: {
            hwCompletionRate: firstFiniteNumber(lcmsStats?.hwCompletionRate),
            hwAvgScore: firstFiniteNumber(lcmsStats?.hwAvgScore),
            testAvgScore: firstFiniteNumber(lcmsStats?.testAvgScore),
        },
        source: {
            hasLegacy: Boolean(dashboardItem),
            hasJourney: Boolean(learnerRow),
            hasCsi: Boolean(csiSnapshot),
        },
        syncedAt: firstNonEmptyValue(learnerRow?.synced_at, dashboardItem?.syncedAt, cachedCsiLegacySnapshot?.refreshedAt),
    };
}

function buildUnifiedStudentUniverse({ dashboardItems = [], learnerRows = [], csiSnapshotById = new Map(), purchaseStatsById = new Map(), lcmsStatsById = {} } = {}) {
    const legacyById = buildLegacyStudentItemIndex(dashboardItems);
    const learnerById = new Map(learnerRows.map(row => [String(row.student_id), row]));
    const studentIds = new Set([
        ...legacyById.keys(),
        ...learnerById.keys(),
        ...csiSnapshotById.keys(),
        ...purchaseStatsById.keys(),
    ]);

    const merged = [...studentIds].map(studentId => buildUnifiedDashboardItem(
        legacyById.get(studentId) || null,
        learnerById.get(studentId) || null,
        csiSnapshotById.get(studentId) || null,
        purchaseStatsById.get(studentId) || null,
        lcmsStatsById[studentId] || lcmsStatsById[Number(studentId)] || null,
    ));

    return classifyHealthMovement(merged).sort((a, b) => {
        const remainingDelta = (Number(b.renewal?.remainingSessions) || 0) - (Number(a.renewal?.remainingSessions) || 0);
        if (remainingDelta !== 0) return remainingDelta;
        const targetDelta = (Number(b.health?.scoreTarget) || 0) - (Number(a.health?.scoreTarget) || 0);
        if (targetDelta !== 0) return targetDelta;
        return String(a.student?.name || '').localeCompare(String(b.student?.name || ''), 'vi');
    });
}

function calculateSourceCoverage(data = []) {
    const total = data.length;
    const count = predicate => data.filter(predicate).length;
    return {
        total,
        withLegacy: count(item => item.source?.hasLegacy),
        withJourney: count(item => item.source?.hasJourney),
        withCsi: count(item => item.source?.hasCsi),
        withBaseScore: count(item => item.health?.scoreBase !== null && item.health?.scoreBase !== undefined && item.health?.scoreBase !== ''),
        withTargetScore: count(item => item.health?.scoreTarget !== null && item.health?.scoreTarget !== undefined && item.health?.scoreTarget !== ''),
        withMovementBase: count(item => {
            const movement = String(item.movement?.group || '').trim();
            return Boolean(movement) && movement !== '7. Mới từ Zeus (chưa có base)';
        }),
        withRevenue: count(item => Number(item.renewal?.revenue || 0) > 0),
    };
}

function pad2(value) {
    return String(value).padStart(2, '0');
}

function buildMonthMeta(date) {
    const year = date.getUTCFullYear();
    const month = date.getUTCMonth() + 1;
    const quarter = Math.floor((month - 1) / 3) + 1;
    const lastDay = new Date(Date.UTC(year, date.getUTCMonth() + 1, 0));
    const label = `${year}-${pad2(month)}`;
    return {
        label,
        fromDate: `${year}-${pad2(month)}-01`,
        toDate: `${lastDay.getUTCFullYear()}-${pad2(lastDay.getUTCMonth() + 1)}-${pad2(lastDay.getUTCDate())}`,
        periodMonth: label,
        periodQuarter: `Q${quarter}/${year}`,
        periodYear: String(year),
        periodWeek: '',
    };
}

function parseMonthLabel(label) {
    const [year, month] = String(label || '').split('-').map(Number);
    if (!year || !month) return new Date(Date.UTC(2025, 10, 1));
    return new Date(Date.UTC(year, month - 1, 1));
}

function buildCsiMonthTimeline(startLabel = '2025-11') {
    const start = parseMonthLabel(startLabel);
    const now = new Date();
    const months = [];
    for (let cursor = new Date(start); cursor <= now; cursor = new Date(Date.UTC(cursor.getUTCFullYear(), cursor.getUTCMonth() + 1, 1))) {
        months.push(buildMonthMeta(cursor));
    }
    return months;
}

function categorizeLegacyScore(score, allowNew = false) {
    const numeric = firstFiniteNumber(score);
    if (allowNew && numeric === null) return 'Mới';
    if ((numeric ?? 0) >= 85) return '3. Khỏe mạnh (85-100)';
    if ((numeric ?? 0) >= 60) return '2. Cần chú ý (60-84)';
    return '1. Báo động (<60)';
}

function deriveLegacyMovementGroup(baseCategory, targetCategory) {
    if (baseCategory === 'Mới') {
        if (String(targetCategory).startsWith('3.')) return '9. Mới: Khỏe mạnh (85-100)';
        if (String(targetCategory).startsWith('2.')) return '8. Mới: Cần chú ý (60-84)';
        return '7. Mới: Báo động (<60)';
    }

    const matrix = {
        '3. Khỏe mạnh (85-100) -> 3. Khỏe mạnh (85-100)': '3a. Ổn định (Khỏe mạnh)',
        '2. Cần chú ý (60-84) -> 3. Khỏe mạnh (85-100)': '1. Cứu vãn thành công',
        '1. Báo động (<60) -> 3. Khỏe mạnh (85-100)': '1. Cứu vãn thành công',
        '1. Báo động (<60) -> 2. Cần chú ý (60-84)': '2. Có cải thiện (Chưa an toàn)',
        '2. Cần chú ý (60-84) -> 2. Cần chú ý (60-84)': '3b. Ổn định (Cần chú ý)',
        '3. Khỏe mạnh (85-100) -> 2. Cần chú ý (60-84)': '4. Dấu hiệu sa sút',
        '2. Cần chú ý (60-84) -> 1. Báo động (<60)': '5. Tụt dốc nghiêm trọng',
        '3. Khỏe mạnh (85-100) -> 1. Báo động (<60)': '5. Tụt dốc nghiêm trọng',
        '1. Báo động (<60) -> 1. Báo động (<60)': '6. Nguy hiểm kéo dài',
    };

    return matrix[`${baseCategory} -> ${targetCategory}`] || '7. Mới từ Zeus (chưa có base)';
}

function buildMonthlySnapshotIndex(monthSnapshots = [], options = {}) {
    const studentIds = new Set();
    monthSnapshots.forEach(snapshot => {
        snapshot.rows.forEach(row => studentIds.add(String(row.student_id)));
    });

    const targetMonthLabel = String(options.targetMonthLabel || '').trim();
    let fromMonthLabel = String(options.fromMonthLabel || '').trim();
    let toMonthLabel = String(options.toMonthLabel || '').trim();
    if (fromMonthLabel && toMonthLabel && fromMonthLabel > toMonthLabel) {
        [fromMonthLabel, toMonthLabel] = [toMonthLabel, fromMonthLabel];
    }
    const scopedSnapshots = (fromMonthLabel || toMonthLabel)
        ? monthSnapshots.filter(snapshot => (!fromMonthLabel || snapshot.label >= fromMonthLabel) && (!toMonthLabel || snapshot.label <= toMonthLabel))
        : monthSnapshots;

    const byStudentId = new Map();
    studentIds.forEach(studentId => {
        let targetSnapshot = null;
        if (targetMonthLabel) {
            targetSnapshot = monthSnapshots.find(snapshot => snapshot.label === targetMonthLabel && snapshot.byStudentId.has(studentId)) || null;
        } else if (fromMonthLabel || toMonthLabel) {
            targetSnapshot = [...scopedSnapshots].reverse().find(snapshot => snapshot.byStudentId.has(studentId)) || null;
        } else {
            const preferredSnapshot = monthSnapshots.find(snapshot => snapshot.byStudentId.has(studentId) && snapshot.isPreferredTarget);
            targetSnapshot = preferredSnapshot || [...monthSnapshots].reverse().find(snapshot => snapshot.byStudentId.has(studentId)) || null;
        }
        if (!targetSnapshot) return;
        const targetRow = targetSnapshot.byStudentId.get(studentId);
        const targetIndex = monthSnapshots.findIndex(snapshot => snapshot.label === targetSnapshot.label);
        const baseSnapshot = monthSnapshots.slice(0, Math.max(targetIndex, 0)).reverse().find(snapshot => snapshot.byStudentId.has(studentId)) || null;
        const baseRow = baseSnapshot ? baseSnapshot.byStudentId.get(studentId) : null;

        const targetScore = firstFiniteNumber(targetRow?.health_score) ?? 0;
        const baseScore = firstFiniteNumber(baseRow?.health_score);
        const targetCategory = categorizeLegacyScore(targetScore);
        const baseCategory = categorizeLegacyScore(baseScore, true);
        const movementGroup = deriveLegacyMovementGroup(baseCategory, targetCategory);
        const teacherDisruptionRate = (Number(targetRow?.teacher_noshow) || 0) / Math.max(Number(targetRow?.total_scheduled) || 0, 1);
        const unfinishedRate = Math.max(
            (Number(targetRow?.total_scheduled) || 0)
                - (Number(targetRow?.total_success) || 0)
                - (Number(targetRow?.student_noshow) || 0)
                - (Number(targetRow?.student_half) || 0),
            0,
        ) / Math.max(Number(targetRow?.total_scheduled) || 0, 1);
        const periodWeekSpan = calculatePeriodWeekSpan(targetSnapshot.fromDate, targetSnapshot.toDate);
        const learningPace = Number(((Number(targetRow?.total_success) || 0) / periodWeekSpan).toFixed(2));
        const cumulativeRows = monthSnapshots
            .slice(0, targetIndex + 1)
            .map(snapshot => snapshot.byStudentId.get(studentId))
            .filter(Boolean);
        const cumulativeTeacherNoshow = cumulativeRows.reduce((sum, row) => sum + (Number(row?.teacher_noshow) || 0), 0);
        const cumulativeTotalScheduled = cumulativeRows.reduce((sum, row) => sum + (Number(row?.total_scheduled) || 0), 0);
        const teacherDisruptionCumulative = cumulativeTotalScheduled
            ? Number((cumulativeTeacherNoshow / cumulativeTotalScheduled).toFixed(4))
            : Number(teacherDisruptionRate.toFixed(4));

        byStudentId.set(studentId, {
            studentId,
            targetMonth: targetSnapshot,
            baseMonth: baseSnapshot || null,
            targetRow,
            baseRow,
            period: {
                date: targetSnapshot.toDate,
                month: targetSnapshot.periodMonth,
                quarter: targetSnapshot.periodQuarter,
                year: targetSnapshot.periodYear,
                week: targetSnapshot.periodWeek,
            },
            health: {
                scoreTarget: targetScore,
                scoreBase: baseScore,
                variance: baseScore === null ? 0 : Number((targetScore - baseScore).toFixed(1)),
                targetCategory,
                baseCategory,
                managementScore: targetScore,
                learningPace,
            },
            movementGroup,
            operation: {
                teacherDisruptionRate,
                unfinishedRate,
                teacherDisruptionCumulative,
            },
            csi: {
                healthCategory: targetRow?.health_category || mapCsiCategoryToTargetCategory(targetCategory),
                teacherWarning: targetRow?.teacher_warning || '',
                totalScheduled: Number(targetRow?.total_scheduled) || 0,
                totalSuccess: Number(targetRow?.total_success) || 0,
                studentNoshow: Number(targetRow?.student_noshow) || 0,
                studentHalf: Number(targetRow?.student_half) || 0,
                teacherNoshow: Number(targetRow?.teacher_noshow) || 0,
                successRate: firstFiniteNumber(targetRow?.success_rate),
                avgPerWeek: firstFiniteNumber(targetRow?.avg_per_week),
                ontrackScore: firstFiniteNumber(targetRow?.ontrack_score),
                courseNames: targetRow?.course_names || '',
            },
        });
    });

    return byStudentId;
}

function buildPurchaseHistoryStatsByStudent(rows = []) {
    const statsByStudent = new Map();
    rows.forEach(row => {
        const studentId = String(row.student_id || '');
        if (!studentId) return;
        const amount = Number(row.order_amount || 0);
        const orderDate = String(row.order_date || '');
        const monthLabel = orderDate.slice(0, 7);
        const current = statsByStudent.get(studentId) || {
            paidOrderCount: 0,
            firstPaidOrderDate: '',
            lastPaidOrderDate: '',
            paidOrderAmountTotal: 0,
            monthlyPaidOrders: {},
        };
        if (amount > 0) {
            current.paidOrderCount += 1;
            current.paidOrderAmountTotal += amount;
            current.firstPaidOrderDate = current.firstPaidOrderDate && current.firstPaidOrderDate < orderDate ? current.firstPaidOrderDate : orderDate;
            current.lastPaidOrderDate = current.lastPaidOrderDate && current.lastPaidOrderDate > orderDate ? current.lastPaidOrderDate : orderDate;
            if (monthLabel) {
                const monthStats = current.monthlyPaidOrders[monthLabel] || { count: 0, amount: 0 };
                monthStats.count += 1;
                monthStats.amount += amount;
                current.monthlyPaidOrders[monthLabel] = monthStats;
            }
        }
        statsByStudent.set(studentId, current);
    });
    return statsByStudent;
}

async function applyLearnerJourneyRoleScope(rows, user) {
    if (!user) return [];
    if (user.role === ROLE.HEAD) return rows;

    const allowedCssScopes = await getAllowedCssScopes(user);
    if (!allowedCssScopes || !allowedCssScopes.length) return [];
    const allowed = new Set(allowedCssScopes.map(value => String(value).trim().toLowerCase()));
    return rows.filter(row => allowed.has(String(row.css || '').trim().toLowerCase()));
}

function splitQueryList(value) {
    return String(value || '')
        .split(',')
        .map(item => item.trim())
        .filter(Boolean);
}

function splitStoredList(text, separator) {
    return String(text || '')
        .split(separator)
        .map(item => item.trim())
        .filter(Boolean);
}

function buildLearnerJourneyFilterOptions(rows = []) {
    const courseGroupCounts = new Map();
    const classSizeCounts = new Map();

    rows.forEach(row => {
        new Set(splitStoredList(row.package_groups, '|')).forEach(value => {
            courseGroupCounts.set(value, (courseGroupCounts.get(value) || 0) + 1);
        });
        new Set(splitStoredList(row.class_sizes, ',')).forEach(value => {
            classSizeCounts.set(value, (classSizeCounts.get(value) || 0) + 1);
        });
    });

    return {
        courseGroups: [...courseGroupCounts.entries()]
            .sort((a, b) => a[0].localeCompare(b[0], 'en'))
            .map(([value, count]) => ({ value, count })),
        classSizes: [...classSizeCounts.entries()]
            .sort((a, b) => a[0].localeCompare(b[0], 'en'))
            .map(([value, count]) => ({ value, count })),
    };
}

function filterLearnerJourneyRows(rows, query = {}) {
    const search = String(query.search || '').trim().toLowerCase();
    const css = String(query.css || '').trim().toLowerCase();
    const studentStatus = String(query.studentStatus || '').trim();
    const selectedCourseGroups = splitQueryList(query.courseGroups);
    const selectedClassSizes = splitQueryList(query.classSizes);

    return rows.filter(row => {
        if (css && String(row.css || '').trim().toLowerCase() !== css) return false;

        if (selectedCourseGroups.length) {
            const groups = splitStoredList(row.package_groups, '|');
            if (!selectedCourseGroups.some(group => groups.includes(group))) return false;
        }

        if (selectedClassSizes.length) {
            const classSizes = splitStoredList(row.class_sizes, ',');
            if (!selectedClassSizes.some(size => classSizes.includes(size))) return false;
        }

        if (studentStatus) {
            const normalizedStatus = deriveStudentStatus(normalizeJourneyStatusLabel(row.journey_status));
            if (normalizedStatus !== studentStatus) return false;
        }

        if (!search) return true;

        const haystack = [
            row.student_id,
            row.student_name,
            row.email,
            row.phone,
            row.css,
            row.class_sizes,
            row.package_names,
            row.package_groups,
            row.journey_status,
        ].join(' ').toLowerCase();

        return haystack.includes(search);
    });
}

function serializeLearnerJourneyStudent(row, dashboardRow = {}) {
    const purchasedSessions = Number(row.purchased_sessions) || 0;
    const unscheduledSessions = Number(row.unscheduled_sessions) || 0;
    const scheduledSessions = Number(row.scheduled_sessions) || 0;
    const completedSessions = Number(row.completed_sessions) || 0;
    const cancelledSessions = Number(row.cancelled_sessions) || 0;
    const remainingSessions = Number(row.remaining_sessions) || 0;
    const packageNames = row.package_names || dashboardRow.renewalProduct || '';
    const journeyStatus = normalizeJourneyStatusLabel(row.journey_status) || dashboardRow.lifecycleStatus || 'Chưa rõ hành trình';
    const studentStatus = dashboardRow.studentStatus || deriveStudentStatus(journeyStatus);
    const resolvedRenewalStatus = dashboardRow.renewalStatus || (remainingSessions > 0 ? '⏳ Chưa đến hạn' : '⏳ Chưa gia hạn');

    return {
        ...dashboardRow,
        id: row.student_id,
        name: row.student_name,
        email: row.email || dashboardRow.email || '',
        phone: row.phone || dashboardRow.phone || '',
        css: row.css || dashboardRow.css || '',
        classSizes: row.class_sizes || '',
        packageNames,
        packageGroups: row.package_groups || '',
        teacherTypes: row.teacher_types || '',
        purchasedSessions,
        unscheduledSessions,
        scheduledSessions,
        completedSessions,
        cancelledSessions,
        remainingSessions,
        firstLessonStarttime: row.first_lesson_starttime || '',
        lastLessonStarttime: row.last_lesson_starttime || '',
        latestOrderAmount: Number(row.latest_order_amount) || Number(dashboardRow.latestOrderAmount) || 0,
        latestOrderDate: row.latest_order_date || dashboardRow.latestOrderDate || '',
        journeyStatus,
        studentStatus,
        renewalProduct: packageNames,
        productType: dashboardRow.productType || deriveProductType(row, packageNames),
        teacherType: dashboardRow.teacherType || normalizeTeacherTypeValue(row.teacher_types) || deriveTeacherType(packageNames),
        lifecycleStatus: journeyStatus,
        renewalStatus: resolvedRenewalStatus,
    };
}

function pickLatestTimestamp(...values) {
    return values
        .map(value => String(value || '').trim())
        .filter(Boolean)
        .sort()
        .slice(-1)[0] || null;
}

async function getCachedDashboardData() {
    const now = Date.now();
    if (cachedDashboardData && now - cachedDashboardAt < DATA_CACHE_TTL_MS) {
        return { allData: cachedDashboardData, filterOptions: cachedDashboardFilterOptions, lastSyncedAt: cachedDashboardLastSyncedAt };
    }

    const rows = await dbAll('SELECT * FROM dashboard_data');
    const allData = classifyHealthMovement(rows.map(mapDbRow));
    cachedDashboardData = allData;
    cachedDashboardFilterOptions = getFilterOptions(allData);
    cachedDashboardLastSyncedAt = rows[0]?.synced_at || null;
    cachedDashboardAt = now;
    return { allData, filterOptions: cachedDashboardFilterOptions, lastSyncedAt: cachedDashboardLastSyncedAt };
}

async function getCachedCsiLegacySnapshot(legacyStudentCount = 0) {
    const now = Date.now();
    if (cachedCsiLegacySnapshot && now - cachedCsiLegacySnapshotAt < CSI_SNAPSHOT_CACHE_TTL_MS) {
        return cachedCsiLegacySnapshot;
    }

    try {
        const months = buildCsiMonthTimeline('2025-11');
        const summaries = [];
        for (const month of months) {
            const summary = await getCsiSummary({ fromDate: month.fromDate, toDate: month.toDate });
            summaries.push({ ...month, totalStudents: Number(summary.total_students || 0) });
        }

        const preferredTargetMonth = legacyStudentCount
            ? summaries
                .map(month => ({ ...month, diff: Math.abs(Number(month.totalStudents || 0) - Number(legacyStudentCount || 0)) }))
                .sort((a, b) => a.diff - b.diff || b.label.localeCompare(a.label))[0]
            : [...summaries].reverse().find(month => Number(month.totalStudents || 0) > 0);

        const monthSnapshots = [];
        for (const month of summaries) {
            const rows = Number(month.totalStudents || 0)
                ? await getCsiStudents({ fromDate: month.fromDate, toDate: month.toDate }, { sortBy: 'student_id', sortDir: 'asc' })
                : [];
            monthSnapshots.push({
                ...month,
                rows,
                byStudentId: buildCsiStudentIndex(rows),
                isPreferredTarget: preferredTargetMonth ? month.label === preferredTargetMonth.label : false,
            });
        }

        cachedCsiLegacySnapshot = {
            preferredTargetMonth,
            monthSnapshots,
            byStudentId: buildMonthlySnapshotIndex(monthSnapshots),
            refreshedAt: new Date().toISOString(),
        };
        cachedCsiLegacySnapshotAt = now;
        return cachedCsiLegacySnapshot;
    } catch (error) {
        console.error('[cache] CSI legacy snapshot refresh failed:', error.message);
        if (cachedCsiLegacySnapshot) {
            return { ...cachedCsiLegacySnapshot, stale: true, error: error.message };
        }
        return {
            preferredTargetMonth: null,
            monthSnapshots: [],
            byStudentId: new Map(),
            refreshedAt: null,
            stale: true,
            error: error.message,
        };
    }
}

async function getCachedLcmsBatchStats(userIds = []) {
    const ids = [...new Set((userIds || []).map(value => Number(value)).filter(Number.isFinite).filter(value => value > 0))].sort((a, b) => a - b);
    if (!ids.length) return {};
    const cacheKey = ids.join(',');
    const now = Date.now();
    if (cachedLcmsBatchStats && cachedLcmsBatchStatsKey === cacheKey && now - cachedLcmsBatchStatsAt < LCMS_BATCH_CACHE_TTL_MS) {
        return cachedLcmsBatchStats;
    }

    try {
        cachedLcmsBatchStats = await getStudentLcmsStatsBatch(ids);
        cachedLcmsBatchStatsKey = cacheKey;
        cachedLcmsBatchStatsAt = now;
        return cachedLcmsBatchStats;
    } catch (error) {
        console.error('[cache] LCMS batch stats refresh failed:', error.message);
        return cachedLcmsBatchStats || {};
    }
}

async function getCachedLcmsDetail(studentId) {
    const key = String(studentId || '').trim();
    if (!key) return null;
    const cached = cachedLcmsDetails.get(key);
    const now = Date.now();
    if (cached && now - cached.at < LCMS_DETAIL_CACHE_TTL_MS) {
        return cached.payload;
    }

    try {
        const payload = await getStudentLcmsDetailByUserId(Number(key));
        cachedLcmsDetails.set(key, { at: now, payload });
        return payload;
    } catch (error) {
        console.error('[cache] LCMS detail refresh failed:', error.message);
        return cached?.payload || null;
    }
}

function normalizeTimeScope(scope = {}) {
    if (typeof scope === 'string') {
        const targetMonthLabel = String(scope || '').trim();
        return {
            targetMonthLabel,
            fromMonthLabel: targetMonthLabel,
            toMonthLabel: targetMonthLabel,
            mode: targetMonthLabel ? 'month' : '',
        };
    }

    let fromMonthLabel = String(scope?.fromMonthLabel || '').trim();
    let toMonthLabel = String(scope?.toMonthLabel || '').trim();
    if (fromMonthLabel && toMonthLabel && fromMonthLabel > toMonthLabel) {
        [fromMonthLabel, toMonthLabel] = [toMonthLabel, fromMonthLabel];
    }
    const targetMonthLabel = String(scope?.targetMonthLabel || '').trim();
    const mode = String(scope?.mode || '').trim();
    return {
        targetMonthLabel,
        fromMonthLabel,
        toMonthLabel,
        mode,
    };
}

async function buildUnifiedDataPayload(scope = {}) {
    const { targetMonthLabel, fromMonthLabel, toMonthLabel, mode } = normalizeTimeScope(scope);
    const [dashboardSource, learnerRows, purchaseHistoryRows] = await Promise.all([
        getCachedDashboardData(),
        listLearnerJourneyStudents(),
        listLearnerJourneyPurchaseHistory(),
    ]);
    const csiSnapshot = await getCachedCsiLegacySnapshot(dashboardSource.allData.length);
    const purchaseStatsById = buildPurchaseHistoryStatsByStudent(purchaseHistoryRows);
    const lcmsStatsById = await getCachedLcmsBatchStats([
        ...dashboardSource.allData.map(item => item.student?.id),
        ...learnerRows.map(row => row.student_id),
    ]);
    const csiSnapshotById = (targetMonthLabel || fromMonthLabel || toMonthLabel)
        ? buildMonthlySnapshotIndex(csiSnapshot.monthSnapshots, { targetMonthLabel, fromMonthLabel, toMonthLabel })
        : csiSnapshot.byStudentId;

    const allData = buildUnifiedStudentUniverse({
        dashboardItems: dashboardSource.allData,
        learnerRows,
        csiSnapshotById,
        purchaseStatsById,
        lcmsStatsById,
    });

    const purchaseHistorySyncedAt = purchaseHistoryRows[0]?.synced_at || null;
    const lastSyncedAt = pickLatestTimestamp(
        dashboardSource.lastSyncedAt,
        learnerRows[0]?.synced_at,
        purchaseHistorySyncedAt,
        csiSnapshot.refreshedAt,
    );

    return {
        allData,
        filterOptions: getFilterOptions(allData),
        lastSyncedAt,
        timeScope: {
            targetMonthLabel,
            fromMonthLabel,
            toMonthLabel,
            mode,
        },
    };
}

async function getCachedUnifiedData() {
    const now = Date.now();
    if (cachedUnifiedData && now - cachedUnifiedAt < DATA_CACHE_TTL_MS) {
        return { allData: cachedUnifiedData, filterOptions: cachedUnifiedFilterOptions, lastSyncedAt: cachedUnifiedLastSyncedAt };
    }

    const payload = await buildUnifiedDataPayload();
    cachedUnifiedData = payload.allData;
    cachedUnifiedFilterOptions = payload.filterOptions;
    cachedUnifiedLastSyncedAt = payload.lastSyncedAt;
    cachedUnifiedAt = now;
    return payload;
}

async function applyRoleScope(allData, user) {
    if (!user) return [];
    if (user.role === ROLE.HEAD) return allData;

    const allowedCssScopes = await getAllowedCssScopes(user);
    if (!allowedCssScopes || !allowedCssScopes.length) return [];
    const allowed = new Set(allowedCssScopes.map(value => String(value).trim().toLowerCase()));
    return allData.filter(row => allowed.has(String(row.student.css || '').trim().toLowerCase()));
}

async function getLearnerJourneySource(query = {}, user) {
    const rows = await listLearnerJourneyStudents();
    const scopedJourneyRows = await applyLearnerJourneyRoleScope(rows, user);
    const scopedCssRows = filterLearnerJourneyRows(scopedJourneyRows, { css: query.css });
    const baseJourneyRows = filterLearnerJourneyRows(scopedCssRows, query);
    const filterOptions = buildLearnerJourneyFilterOptions(scopedCssRows);
    const timeScope = resolveTimeScopedMonthWindow(query);
    const { allData } = (timeScope.targetMonthLabel || timeScope.fromMonthLabel || timeScope.toMonthLabel)
        ? await buildUnifiedDataPayload(timeScope)
        : await getCachedUnifiedData();
    const scopedDashboardRows = await applyRoleScope(allData, user);
    const dashboardById = buildDashboardStudentIndex(scopedDashboardRows);
    const hasDashboardScopeFilter = Boolean(
        query.quarter
        || query.month
        || query.fromDate
        || query.toDate
        || query.healthMovementGroup
        || query.group
        || query.targetCategory
        || query.baseCategory
        || query.renewalStatus
        || query.product
        || query.lifecycleStatus
        || query.studentStatus
    );
    const allowedDashboardIds = hasDashboardScopeFilter
        ? new Set(applyFilters(scopedDashboardRows, query).map(item => String(item.student.id)))
        : null;
    const filteredJourneyRows = hasDashboardScopeFilter
        ? baseJourneyRows.filter(row => allowedDashboardIds.has(String(row.student_id)))
        : baseJourneyRows;
    const mergedStudents = filteredJourneyRows.map(row => serializeLearnerJourneyStudent(row, dashboardById.get(String(row.student_id))));
    const lastSyncedAt = pickLatestTimestamp(rows[0]?.synced_at, cachedUnifiedLastSyncedAt);
    return { mergedStudents, scopedJourneyRows, filteredJourneyRows, filterOptions, lastSyncedAt };
}

function serializePurchaseHistoryRow(row) {
    return {
        studentId: row.student_id,
        orderId: row.order_id,
        orderAmount: Number(row.order_amount) || 0,
        orderDate: row.order_date || '',
        renewalDate: row.renewal_date || row.order_date || '',
        classSizes: row.class_sizes || '',
        packageNames: row.package_names || '',
        packageGroups: row.package_groups || '',
        purchasedSessions: Number(row.purchased_sessions) || 0,
        unscheduledSessions: Number(row.unscheduled_sessions) || 0,
        scheduledSessions: Number(row.scheduled_sessions) || 0,
        completedSessions: Number(row.completed_sessions) || 0,
        cancelledSessions: Number(row.cancelled_sessions) || 0,
        remainingSessions: Number(row.remaining_sessions) || 0,
        syncedAt: row.synced_at || '',
    };
}

async function getScopedStudentDetail(studentId, user) {
    const learnerRows = await applyLearnerJourneyRoleScope(await listLearnerJourneyStudents(), user);
    const learnerRow = learnerRows.find(row => String(row.student_id) === String(studentId));

    const { allData } = await getCachedUnifiedData();
    const scopedData = await applyRoleScope(allData, user);
    const dashboardById = buildDashboardStudentIndex(scopedData);
    const dashboardRow = dashboardById.get(String(studentId));

    if (!learnerRow && !dashboardRow) return null;

    return learnerRow
        ? serializeLearnerJourneyStudent(learnerRow, dashboardRow || {})
        : dashboardRow;
}

function paginate(items, page, pageSize) {
    const total = items.length;
    const safePageSize = Math.min(Math.max(Number(pageSize) || 25, 1), 10000);
    const totalPages = Math.max(Math.ceil(total / safePageSize), 1);
    const safePage = Math.min(Math.max(Number(page) || 1, 1), totalPages);
    const start = (safePage - 1) * safePageSize;
    return {
        page: safePage,
        pageSize: safePageSize,
        total,
        totalPages,
        rows: items.slice(start, start + safePageSize),
    };
}

function resolveTimeScopedMonthWindow(filters = {}) {
    if (filters.month) {
        const month = String(filters.month).trim();
        return {
            targetMonthLabel: month,
            fromMonthLabel: month,
            toMonthLabel: month,
            mode: 'month',
        };
    }

    const fromMeta = buildPeriodMetaFromDateValue(filters.fromDate);
    const toMeta = buildPeriodMetaFromDateValue(filters.toDate);
    let fromMonthLabel = String(fromMeta?.periodMonth || '').trim();
    let toMonthLabel = String(toMeta?.periodMonth || '').trim();
    if (fromMonthLabel && toMonthLabel && fromMonthLabel > toMonthLabel) {
        [fromMonthLabel, toMonthLabel] = [toMonthLabel, fromMonthLabel];
    }
    if (!fromMonthLabel && toMonthLabel) fromMonthLabel = toMonthLabel;
    if (!toMonthLabel && fromMonthLabel) toMonthLabel = fromMonthLabel;
    if (!fromMonthLabel && !toMonthLabel) {
        return {
            targetMonthLabel: '',
            fromMonthLabel: '',
            toMonthLabel: '',
            mode: '',
        };
    }
    const sameMonth = fromMonthLabel && toMonthLabel && fromMonthLabel === toMonthLabel;
    return {
        targetMonthLabel: sameMonth ? fromMonthLabel : '',
        fromMonthLabel,
        toMonthLabel,
        mode: sameMonth ? 'single-range' : 'range',
    };
}

async function getScopedDashboardSource(filters = {}, user, options = {}) {
    const timeScope = resolveTimeScopedMonthWindow(filters);
    const source = (timeScope.targetMonthLabel || timeScope.fromMonthLabel || timeScope.toMonthLabel)
        ? await buildUnifiedDataPayload(timeScope)
        : await getCachedUnifiedData();
    const { allData, lastSyncedAt } = source;
    const scopedData = await applyRoleScope(allData, user);
    const filteredData = applyFilters(scopedData, filters);
    const searchedData = applyStudentSearch(filteredData, options.search);
    return { allData, scopedData, filteredData, searchedData, lastSyncedAt, timeScope: source.timeScope || timeScope };
}

async function getDashboardPayload(filters = {}, user) {
    const { allData, scopedData, filteredData, lastSyncedAt, timeScope } = await getScopedDashboardSource(filters, user);
    const metrics = calculateComprehensiveMetrics(filteredData);
    const csiSnapshot = await getCachedCsiLegacySnapshot(allData.length);
    return {
        ...metrics,
        viewer: user,
        filters,
        filterOptions: getFilterOptions(scopedData),
        rowCount: filteredData.length,
        totalCachedRows: allData.length,
        scopedRows: scopedData.length,
        lastSyncedAt,
        sourceCoverage: {
            filtered: calculateSourceCoverage(filteredData),
            scoped: calculateSourceCoverage(scopedData),
        },
        compareBasis: {
            defaultTargetMonth: csiSnapshot?.preferredTargetMonth?.label || '',
            activeTargetMonth: timeScope?.targetMonthLabel || '',
            activeTargetRange: {
                fromMonth: timeScope?.fromMonthLabel || '',
                toMonth: timeScope?.toMonthLabel || '',
                mode: timeScope?.mode || '',
            },
        },
        generatedAt: new Date().toISOString(),
    };
}

io.use(async (socket, next) => {
    try {
        const token = socket.handshake.auth?.token || socket.handshake.query?.token;
        const user = await getUserByToken(token);
        if (!user) {
            return next(new Error('Unauthorized socket session'));
        }
        socket.user = user;
        socket.authToken = token;
        next();
    } catch (error) {
        next(error);
    }
});

app.get('/health', (_req, res) => {
    res.json({ ok: true, service: 'CRM-Dashboard', time: new Date().toISOString() });
});

app.post('/api/auth/login', async (req, res) => {
    try {
        const { email, password } = req.body || {};
        const result = await login(email, password);
        res.json(result);
    } catch (error) {
        console.error('[api] login failed:', error.message);
        res.status(error.status || 500).json({ error: error.message });
    }
});

app.post('/api/auth/logout', requireAuth, async (req, res) => {
    try {
        await logout(req.authToken);
        res.json({ ok: true });
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

app.get('/api/auth/me', requireAuth, async (req, res) => {
    res.json({ user: req.user });
});

app.post('/api/auth/change-password', requireAuth, async (req, res) => {
    try {
        const { currentPassword, newPassword } = req.body || {};
        const user = await changeOwnPassword(req.user, currentPassword, newPassword);
        res.json({ ok: true, user });
    } catch (error) {
        res.status(error.status || 500).json({ error: error.message });
    }
});

app.get('/api/users', requireAuth, requireAnyRole([ROLE.HEAD, ROLE.CSS_MANAGER, ROLE.CSS_TEAM_LEADER]), async (req, res) => {
    try {
        const users = await listUsers(req.user, req.query || {});
        res.json({ users });
    } catch (error) {
        console.error('[api] listUsers failed:', error);
        res.status(error.status || 500).json({ error: error.message });
    }
});

app.get('/api/users/audit', requireAuth, requireAnyRole([ROLE.HEAD, ROLE.CSS_MANAGER, ROLE.CSS_TEAM_LEADER]), async (req, res) => {
    try {
        const logs = await listAuditLogs(req.user, req.query || {});
        res.json({ logs });
    } catch (error) {
        console.error('[api] listAuditLogs failed:', error);
        res.status(error.status || 500).json({ error: error.message });
    }
});

app.post('/api/users', requireAuth, requireAnyRole([ROLE.HEAD, ROLE.CSS_MANAGER, ROLE.CSS_TEAM_LEADER]), async (req, res) => {
    try {
        const user = await createUser(req.user, req.body || {});
        res.status(201).json({ user });
    } catch (error) {
        console.error('[api] createUser failed:', error);
        res.status(error.status || 500).json({ error: error.message });
    }
});

app.patch('/api/users/:id', requireAuth, requireAnyRole([ROLE.HEAD, ROLE.CSS_MANAGER, ROLE.CSS_TEAM_LEADER]), async (req, res) => {
    try {
        const user = await updateUser(req.user, req.params.id, req.body || {});
        res.json({ user });
    } catch (error) {
        console.error('[api] updateUser failed:', error);
        res.status(error.status || 500).json({ error: error.message });
    }
});

app.post('/api/users/:id/reset-password', requireAuth, requireAnyRole([ROLE.HEAD, ROLE.CSS_MANAGER, ROLE.CSS_TEAM_LEADER]), async (req, res) => {
    try {
        const result = await resetUserPassword(req.user, req.params.id, req.body?.newPassword);
        res.json({ ok: true, ...result });
    } catch (error) {
        console.error('[api] resetUserPassword failed:', error);
        res.status(error.status || 500).json({ error: error.message });
    }
});

app.get('/api/dashboard', requireAuth, async (req, res) => {
    try {
        const payload = await getDashboardPayload(req.query || {}, req.user);
        res.json(payload);
    } catch (error) {
        console.error('[api] dashboard failed:', error);
        res.status(500).json({ error: error.message });
    }
});

app.get('/api/students', requireAuth, async (req, res) => {
    try {
        const { searchedData, scopedData, lastSyncedAt } = await getScopedDashboardSource(req.query || {}, req.user, {
            search: req.query.search,
        });
        const learnerRows = await applyLearnerJourneyRoleScope(await listLearnerJourneyStudents(), req.user);
        const learnerFilteredRows = filterLearnerJourneyRows(learnerRows, {
            css: req.query.css,
            courseGroups: req.query.courseGroups,
            classSizes: req.query.classSizes,
        });
        const learnerFilterActive = splitQueryList(req.query.courseGroups).length || splitQueryList(req.query.classSizes).length;
        const learnerAllowedIds = new Set(learnerFilteredRows.map(row => String(row.student_id)));
        const studentRows = learnerFilterActive
            ? searchedData.filter(item => learnerAllowedIds.has(String(item.student.id)))
            : searchedData;
        const learnerById = new Map(learnerRows.map(row => [String(row.student_id), row]));
        const downloadAll = String(req.query.download || '') === 'all';
        const paging = downloadAll ? { rows: studentRows, total: studentRows.length, page: 1, pageSize: studentRows.length || 1, totalPages: 1 } : paginate(studentRows, req.query.page, req.query.pageSize);
        res.json({
            students: paging.rows.map(item => serializeStudentRow(item, learnerById.get(String(item.student.id)))),
            total: paging.total,
            page: paging.page,
            pageSize: paging.pageSize,
            totalPages: paging.totalPages,
            scopedRows: scopedData.length,
            lastSyncedAt,
        });
    } catch (error) {
        console.error('[api] students failed:', error);
        res.status(500).json({ error: error.message });
    }
});

app.get('/api/students/:id/detail', requireAuth, async (req, res) => {
    try {
        const student = await getScopedStudentDetail(req.params.id, req.user);
        if (!student) {
            return res.status(404).json({ error: 'Student not found in current scope' });
        }

        const purchaseHistory = (await listLearnerJourneyPurchaseHistory(req.params.id)).map(serializePurchaseHistoryRow);
        const lcms = await getCachedLcmsDetail(req.params.id);
        res.json({ student, purchaseHistory, lcms });
    } catch (error) {
        console.error('[api] student detail failed:', error);
        res.status(500).json({ error: error.message });
    }
});

app.get('/api/learner-journey/students', requireAuth, async (req, res) => {
    try {
        const { mergedStudents, scopedJourneyRows, filterOptions, lastSyncedAt } = await getLearnerJourneySource(req.query || {}, req.user);
        res.json({
            students: mergedStudents,
            total: mergedStudents.length,
            scopedRows: scopedJourneyRows.length,
            filterOptions,
            lastSyncedAt,
            source: 'learner_journey_students',
        });
    } catch (error) {
        console.error('[api] learner journey students failed:', error);
        res.status(500).json({ error: error.message });
    }
});

app.post('/api/sync', requireAuth, requireAnyRole([ROLE.HEAD, ROLE.CSS_MANAGER]), (_req, res) => {
    res.status(202).json({
        message: 'Sync is intentionally separated from web process. Run: node src/scripts/sync.js',
    });
});

app.get('/api/utilities/db-connection-defaults', requireAuth, requireAnyRole([ROLE.HEAD, ROLE.CSS_MANAGER]), (_req, res) => {
    res.json({ defaults: getZeusDbConnectionDefaults() });
});

app.post('/api/utilities/test-db-connection', requireAuth, requireAnyRole([ROLE.HEAD, ROLE.CSS_MANAGER]), async (req, res) => {
    try {
        const result = await testZeusDbConnection(req.body || {});
        res.json(result);
    } catch (error) {
        console.error('[api] test-db-connection failed:', error.message);
        res.status(error.status || 500).json({ error: error.message, code: error.code || null });
    }
});

function buildQuarterDateRange(quarterLabel = '') {
    const match = String(quarterLabel || '').match(/^Q([1-4])\/(\d{4})$/i);
    if (!match) return { fromDate: '', toDate: '' };
    const quarter = Number(match[1]);
    const year = Number(match[2]);
    const startMonthIndex = (quarter - 1) * 3;
    const start = new Date(Date.UTC(year, startMonthIndex, 1));
    const end = new Date(Date.UTC(year, startMonthIndex + 3, 0));
    return {
        fromDate: `${start.getUTCFullYear()}-${pad2(start.getUTCMonth() + 1)}-${pad2(start.getUTCDate())}`,
        toDate: `${end.getUTCFullYear()}-${pad2(end.getUTCMonth() + 1)}-${pad2(end.getUTCDate())}`,
    };
}

function mapTargetCategoryToCsiHealthCategory(category = '') {
    const value = String(category || '').trim();
    if (value.startsWith('3.')) return 'Xanh (Khỏe mạnh)';
    if (value.startsWith('2.')) return 'Vàng (Cảnh báo)';
    if (value.startsWith('1.')) return 'Đỏ (Báo động)';
    return '';
}

async function getCsiRequestFilters(query = {}, user = null) {
    const explicitFromDate = String(query.fromDate || '').trim();
    const explicitToDate = String(query.toDate || '').trim();
    let fromDate = explicitFromDate;
    let toDate = explicitToDate;

    if (!fromDate && !toDate) {
        if (query.month) {
            const monthMeta = buildMonthMeta(parseMonthLabel(query.month));
            fromDate = monthMeta.fromDate;
            toDate = monthMeta.toDate;
        } else if (query.quarter) {
            const quarterRange = buildQuarterDateRange(query.quarter);
            fromDate = quarterRange.fromDate;
            toDate = quarterRange.toDate;
        }
    }

    const filters = {
        search: query.search,
        css: query.css,
        fromDate,
        toDate,
        health_category: query.health_category || mapTargetCategoryToCsiHealthCategory(query.targetCategory),
        teacher_warning: query.teacher_warning,
        program: query.program,
    };

    if (!user) {
        return filters;
    }

    const allowedCssScopes = await getAllowedCssScopes(user);
    if (user.role !== ROLE.HEAD) {
        filters.css_scopes = allowedCssScopes || [];
        if (!filters.css_scopes.length) {
            filters.student_ids = [];
            return filters;
        }
    }

    if (query.studentStatus) {
        const { searchedData } = await getScopedDashboardSource(query, user, { search: query.search });
        filters.student_ids = searchedData
            .map(item => Number(item.student?.id))
            .filter(Number.isFinite)
            .filter(value => value > 0);
    }

    return filters;
}

app.get('/api/csi/summary', requireAuth, async (req, res) => {
    try {
        res.json(await getCsiSummary(await getCsiRequestFilters(req.query || {}, req.user)));
    } catch (error) {
        console.error('[api] csi summary failed:', error);
        res.status(500).json({ error: error.message });
    }
});

app.get('/api/csi/health-distribution', requireAuth, async (req, res) => {
    try {
        res.json(await getCsiHealthDistribution(await getCsiRequestFilters(req.query || {}, req.user)));
    } catch (error) {
        console.error('[api] csi health-distribution failed:', error);
        res.status(500).json({ error: error.message });
    }
});

app.get('/api/csi/css-performance', requireAuth, async (req, res) => {
    try {
        res.json(await getCsiCssPerformance(await getCsiRequestFilters(req.query || {}, req.user)));
    } catch (error) {
        console.error('[api] csi css-performance failed:', error);
        res.status(500).json({ error: error.message });
    }
});

app.get('/api/csi/score-distribution', requireAuth, async (req, res) => {
    try {
        res.json(await getCsiScoreDistribution(await getCsiRequestFilters(req.query || {}, req.user)));
    } catch (error) {
        console.error('[api] csi score-distribution failed:', error);
        res.status(500).json({ error: error.message });
    }
});

app.get('/api/csi/teacher-warning', requireAuth, async (req, res) => {
    try {
        res.json(await getCsiTeacherWarning(await getCsiRequestFilters(req.query || {}, req.user)));
    } catch (error) {
        console.error('[api] csi teacher-warning failed:', error);
        res.status(500).json({ error: error.message });
    }
});

app.get('/api/csi/health-dashboard', requireAuth, async (req, res) => {
    try {
        const payload = await getCsiHealthDashboard(await getCsiRequestFilters(req.query || {}, req.user));
        res.json(payload);
    } catch (error) {
        console.error('[api] csi health dashboard failed:', error);
        res.status(500).json({ error: error.message });
    }
});

io.on('connection', (socket) => {
    console.log(`[socket] Client connected: ${socket.user.email}`);
    let currentFilters = {};

    const emitDashboard = async () => {
        try {
            const payload = await getDashboardPayload(currentFilters, socket.user);
            socket.emit('dataUpdate', payload);
        } catch (error) {
            console.error('[socket] dataUpdate failed:', error);
            socket.emit('dashboardError', { error: error.message });
        }
    };

    socket.on('setFilters', (filters = {}) => {
        currentFilters = filters;
        emitDashboard();
    });

    emitDashboard();
    const interval = setInterval(emitDashboard, 30000);

    socket.on('disconnect', () => {
        clearInterval(interval);
        console.log(`[socket] Client disconnected: ${socket.user.email}`);
    });
});

const PORT = Number(process.env.PORT || 3000);
const HOST = process.env.HOST || '0.0.0.0';

async function startServer() {
    try {
        const bootstrapped = await bootstrapHeadUser();
        if (bootstrapped) {
            console.log('[auth] Bootstrapped default Head account');
            console.log(`[auth] Email: ${bootstrapped.email}`);
            console.log(`[auth] Temporary password: ${bootstrapped.password}`);
        }
        server.listen(PORT, HOST, () => {
            console.log(`Server running on http://127.0.0.1:${PORT}`);
            console.log(`Server bound to ${HOST}:${PORT}`);
            console.log('Auto Google sync is disabled in web process. Run `node src/scripts/sync.js` manually or call POST /api/sync.');
        });
    } catch (error) {
        console.error('[startup] Failed to start server:', error);
        process.exit(1);
    }
}

startServer();
