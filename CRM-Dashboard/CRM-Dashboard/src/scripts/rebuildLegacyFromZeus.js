const fs = require('fs');
const path = require('path');
const db = require('../db/database');
const { getCsiSummary, getCsiStudents } = require('../services/csiService');
const { listLearnerJourneyStudents } = require('../services/learnerJourneyService');

function dbAll(sql, params = []) {
    return new Promise((resolve, reject) => {
        db.all(sql, params, (err, rows) => {
            if (err) reject(err);
            else resolve(rows);
        });
    });
}

function ensureDir(dirPath) {
    fs.mkdirSync(dirPath, { recursive: true });
}

function pad(value) {
    return String(value).padStart(2, '0');
}

function monthRange(date) {
    const year = date.getUTCFullYear();
    const month = date.getUTCMonth() + 1;
    const start = `${year}-${pad(month)}-01`;
    const end = new Date(Date.UTC(year, date.getUTCMonth() + 1, 0));
    return {
        label: `${year}-${pad(month)}`,
        fromDate: start,
        toDate: `${end.getUTCFullYear()}-${pad(end.getUTCMonth() + 1)}-${pad(end.getUTCDate())}`,
    };
}

function previousMonth(date) {
    return new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth() - 1, 1));
}

function calculateHealthScore(studentNoshow = 0, studentHalf = 0) {
    const score = 100 - (Number(studentNoshow || 0) * 10) - (Number(studentHalf || 0) * 5);
    return Math.max(0, Math.min(100, Number(score.toFixed(1))));
}

function categorizeScore(score, allowNew = false) {
    if (allowNew && (score === null || score === undefined)) return 'Mới';
    const value = Number(score || 0);
    if (value >= 85) return '3. Khỏe mạnh (85-100)';
    if (value >= 60) return '2. Cần chú ý (60-84)';
    return '1. Báo động (<60)';
}

function deriveMovementGroup(baseCategory, targetCategory) {
    if (baseCategory === 'Mới') {
        if (targetCategory.startsWith('3.')) return '9. Mới: Khỏe mạnh (85-100)';
        if (targetCategory.startsWith('2.')) return '8. Mới: Cần chú ý (60-84)';
        return '7. Mới: Báo động (<60)';
    }
    const key = `${baseCategory} -> ${targetCategory}`;
    const mapping = {
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
    return mapping[key] || '';
}

function deriveLegacyLikeStatus(baseCategory) {
    if (baseCategory === 'Mới') {
        return {
            activationSpeed: 'Thiếu ngày',
            renewalStatus: 'Bán mới',
            renewalRevenue: 0,
            renewalProduct: 'Onboarding',
            lifecycleStatus: '1. Mới (Onboarding)',
        };
    }
    return {
        activationSpeed: '',
        renewalStatus: '⏳ Chưa gia hạn',
        renewalRevenue: 0,
        renewalProduct: '',
        lifecycleStatus: '4. Hết gói (Gap chờ phí)',
    };
}

function round2(value) {
    return Number.isFinite(Number(value)) ? Number(Number(value).toFixed(2)) : null;
}

function round4(value) {
    return Number.isFinite(Number(value)) ? Number(Number(value).toFixed(4)) : null;
}

function calculatePeriodWeekSpan(fromDate, toDate) {
    const start = Date.parse(fromDate || '');
    const end = Date.parse(toDate || '');
    if (!Number.isFinite(start) || !Number.isFinite(end) || end <= start) return 1;
    return Math.max((end - start) / (7 * 24 * 60 * 60 * 1000), 1);
}

function parseMonthLabel(label) {
    const [year, month] = String(label).split('-').map(Number);
    return new Date(Date.UTC(year, (month || 1) - 1, 1));
}

function makeRowIndex(rows, idField = 'student_id') {
    const map = new Map();
    rows.forEach(row => map.set(String(row[idField]), row));
    return map;
}

async function loadLegacyRows() {
    return dbAll(`
        SELECT
            student_id,
            student_name,
            email,
            phone,
            css,
            score_target,
            score_base,
            variance,
            target_category,
            base_category,
            movement_group,
            teacher_disruption_rate,
            unfinished_rate,
            activation_speed,
            renewal_status,
            renewal_revenue,
            renewal_product,
            remaining_sessions,
            lifecycle_status,
            management_health_score,
            learning_pace,
            teacher_disruption_cumulative
        FROM dashboard_data
        ORDER BY student_id ASC
    `);
}

async function detectTargetMonth(legacyCount) {
    const start = new Date(Date.UTC(2025, 10, 1));
    const now = new Date();
    const candidates = [];

    for (let cursor = new Date(start); cursor <= now; cursor = new Date(Date.UTC(cursor.getUTCFullYear(), cursor.getUTCMonth() + 1, 1))) {
        const range = monthRange(cursor);
        const summary = await getCsiSummary({ fromDate: range.fromDate, toDate: range.toDate });
        const total = Number(summary.total_students || 0);
        candidates.push({ ...range, total, diff: Math.abs(total - legacyCount) });
    }

    candidates.sort((a, b) => a.diff - b.diff || a.label.localeCompare(b.label));
    return { best: candidates[0], candidates };
}

function buildLegacyLikeRow(targetRow, baseRow, learnerRow, monthHistory = [], targetMeta = null) {
    const targetScore = targetRow ? round2(Number(targetRow.health_score || 0)) : 0;
    const baseScore = baseRow ? round2(Number(baseRow.health_score || 0)) : null;
    const targetCategory = categorizeScore(targetScore);
    const baseCategory = baseRow ? categorizeScore(baseScore) : 'Mới';
    const variance = baseRow ? round2(targetScore - baseScore) : 0;
    const movementGroup = deriveMovementGroup(baseCategory, targetCategory);
    const teacherDisruptionRate = targetRow ? round2((Number(targetRow.teacher_noshow || 0) / Math.max(Number(targetRow.total_scheduled || 0), 1))) : 0;
    const unfinishedRate = targetRow
        ? round2((Math.max(Number(targetRow.total_scheduled || 0) - Number(targetRow.total_success || 0) - Number(targetRow.student_noshow || 0) - Number(targetRow.student_half || 0), 0)) / Math.max(Number(targetRow.total_scheduled || 0), 1))
        : 0;
    const periodWeekSpan = calculatePeriodWeekSpan(targetMeta?.fromDate, targetMeta?.toDate);
    const learningPace = targetRow ? round2((Number(targetRow.total_success || 0) / periodWeekSpan)) : 0;
    const cumulativeRows = [
        ...monthHistory.map(month => month.byId.get(String(targetRow?.student_id || baseRow?.student_id || learnerRow?.student_id || ''))).filter(Boolean),
        targetRow,
    ].filter(Boolean);
    const cumulativeTeacherNoshow = cumulativeRows.reduce((sum, row) => sum + (Number(row.teacher_noshow || 0)), 0);
    const cumulativeTotalScheduled = cumulativeRows.reduce((sum, row) => sum + (Number(row.total_scheduled || 0)), 0);
    const teacherDisruptionCumulative = cumulativeTotalScheduled ? round4(cumulativeTeacherNoshow / cumulativeTotalScheduled) : teacherDisruptionRate;
    const statusFields = deriveLegacyLikeStatus(baseCategory);

    return {
        student_id: String(targetRow?.student_id || baseRow?.student_id || learnerRow?.student_id || ''),
        student_name: targetRow?.student_name || baseRow?.student_name || learnerRow?.student_name || '',
        email: targetRow?.email || baseRow?.email || learnerRow?.email || '',
        phone: targetRow?.phone || baseRow?.phone || learnerRow?.phone || '',
        css: targetRow?.css_staff || baseRow?.css_staff || learnerRow?.css || '',
        score_target: targetScore,
        score_base: baseScore,
        variance,
        target_category: targetCategory,
        base_category: baseCategory,
        movement_group: movementGroup,
        teacher_disruption_rate: teacherDisruptionRate,
        unfinished_rate: unfinishedRate,
        activation_speed: statusFields.activationSpeed,
        renewal_status: statusFields.renewalStatus,
        renewal_revenue: statusFields.renewalRevenue,
        renewal_product: statusFields.renewalProduct,
        remaining_sessions: learnerRow ? Number(learnerRow.remaining_sessions || 0) : null,
        lifecycle_status: statusFields.lifecycleStatus,
        management_health_score: targetScore,
        learning_pace: learningPace,
        teacher_disruption_cumulative: teacherDisruptionCumulative,
        package_names: learnerRow?.package_names || '',
        package_groups: learnerRow?.package_groups || '',
        class_sizes: learnerRow?.class_sizes || '',
    };
}

function compareRows(legacyRows, rebuiltRows) {
    const legacyById = makeRowIndex(legacyRows);
    const rebuiltById = makeRowIndex(rebuiltRows);
    const legacyIds = new Set([...legacyById.keys()]);
    const rebuiltIds = new Set([...rebuiltById.keys()]);
    const commonIds = [...legacyIds].filter(id => rebuiltIds.has(id));
    const onlyLegacy = [...legacyIds].filter(id => !rebuiltIds.has(id));
    const onlyRebuilt = [...rebuiltIds].filter(id => !legacyIds.has(id));

    const fieldComparisons = [
        ['score_target', 'numeric'],
        ['score_base', 'numeric-nullable'],
        ['variance', 'numeric'],
        ['target_category', 'text'],
        ['base_category', 'text'],
        ['movement_group', 'text'],
        ['teacher_disruption_rate', 'numeric'],
        ['unfinished_rate', 'numeric'],
        ['activation_speed', 'text'],
        ['renewal_status', 'text'],
        ['renewal_product', 'text'],
        ['lifecycle_status', 'text'],
        ['management_health_score', 'numeric'],
        ['learning_pace', 'numeric'],
        ['teacher_disruption_cumulative', 'numeric'],
    ];

    const stats = {};
    const mismatches = {};

    for (const [field, type] of fieldComparisons) {
        let exact = 0;
        let within5 = 0;
        mismatches[field] = [];
        for (const id of commonIds) {
            const legacy = legacyById.get(id);
            const rebuilt = rebuiltById.get(id);
            const lv = legacy[field];
            const rv = rebuilt[field];
            let isExact = false;
            let isWithin5 = false;

            if (type === 'text') {
                isExact = String(lv || '').trim() === String(rv || '').trim();
                isWithin5 = isExact;
            } else if (type === 'numeric-nullable') {
                const lNull = lv === null || lv === undefined || lv === '';
                const rNull = rv === null || rv === undefined || rv === '';
                isExact = (lNull && rNull) || Number(lv || 0) === Number(rv || 0);
                isWithin5 = (lNull && rNull) || Math.abs(Number(lv || 0) - Number(rv || 0)) <= 5;
            } else {
                isExact = Number(lv || 0) === Number(rv || 0);
                isWithin5 = Math.abs(Number(lv || 0) - Number(rv || 0)) <= 5;
            }

            if (isExact) exact += 1;
            if (isWithin5) within5 += 1;
            if (!isExact && mismatches[field].length < 20) {
                mismatches[field].push({
                    student_id: id,
                    student_name: legacy.student_name,
                    legacy: lv,
                    rebuilt: rv,
                });
            }
        }

        stats[field] = {
            exact,
            exact_pct: commonIds.length ? Number((exact * 100 / commonIds.length).toFixed(1)) : 0,
            within5,
            within5_pct: commonIds.length ? Number((within5 * 100 / commonIds.length).toFixed(1)) : 0,
        };
    }

    return {
        counts: {
            legacy: legacyIds.size,
            rebuilt: rebuiltIds.size,
            common: commonIds.length,
            onlyLegacy: onlyLegacy.length,
            onlyRebuilt: onlyRebuilt.length,
        },
        stats,
        sampleMissingLegacy: onlyLegacy.slice(0, 20),
        sampleMissingRebuilt: onlyRebuilt.slice(0, 20),
        mismatches,
    };
}

function buildMappingMarkdown() {
    return `# Mapping legacy Data_Model ↔ Zeus (best-effort rebuild)\n\n## Quy ước hiện tại\n- Target period được detect tự động bằng tháng CSI có population gần nhất với legacy count.\n- Base period được rebuild theo **tháng baseline sớm nhất trước target mà học viên có CSI data**; mốc tháng liền trước chỉ dùng làm tham chiếu tổng quan.\n- Rebuild hiện tại là **best-effort** từ Zeus DB, dùng logic gần nhất với CSI monthly snapshot + learner journey data.\n\n| Cột legacy | Nguồn Zeus | Công thức rebuild hiện tại | Trạng thái |
|---|---|---|---|
| Student_ID / Tên / Email / SĐT / CSS | CSI + learner journey | lấy trực tiếp theo student_id | direct |
| Score_Target | CSI target month | dùng trực tiếp \`health_score\` của CSI target month | derived |
| Score_Base | CSI baseline month per student | dùng \`health_score\` của tháng sớm nhất trước target mà học viên có data | derived |
| MoM/QoQ_Variance | target/base | target - base, nếu base thiếu thì 0 | derived |
| Phân loại Target | Score_Target | >=85 / >=60 / <60 | derived |
| Phân loại Base | Score_Base | >=85 / >=60 / <60, nếu không có baseline month => Mới | derived |
| Nhóm | base category + target category | matrix 1..9 suy ra từ transition | derived |
| Tỉ lệ gián đoạn do GV | CSI target month | teacher_noshow / total_scheduled | derived |
| Tỉ lệ học dở | CSI target month | (total_scheduled - total_success - noshow - half)/total_scheduled | derived |
| Tốc độ kích hoạt | heuristic | base=Mới => Thiếu ngày, ngược lại rỗng | heuristic |
| Trạng thái gia hạn | heuristic | base=Mới => Bán mới, ngược lại ⏳ Chưa gia hạn | heuristic |
| Doanh thu gia hạn | heuristic | 0 | heuristic |
| Sản phẩm gia hạn chi tiết | heuristic | base=Mới => Onboarding, ngược lại rỗng | heuristic |
| Số buổi còn lại | learner journey | remaining_sessions hiện tại từ Zeus | direct but not period-locked |
| Trạng thái vòng đời | heuristic | base=Mới => 1. Mới (Onboarding), ngược lại 4. Hết gói (Gap chờ phí) | heuristic |
| Điểm sức khỏe quản trị | same as target | = Score_Target | heuristic/derived |
| Nhịp độ học tập | CSI target month | avg_per_week | derived |
| Gián đoạn do GV (tích lũy) | heuristic | same as teacher_disruption_rate | heuristic |
\n## Điểm chưa chắc chắn\n- Legacy có thể dùng rule base period tinh vi hơn; hiện script đang dùng baseline month sớm nhất trước target theo từng học viên.\n- \`Tốc độ kích hoạt\`, \`Trạng thái gia hạn\`, \`Trạng thái vòng đời\`, \`Sản phẩm gia hạn chi tiết\` trong legacy hiện giống snapshot business rút gọn hơn là raw Zeus truth.\n- \`Số buổi còn lại\` trong legacy cũ bị transform âm, nên không nên dùng nó làm ground truth.\n`;
}

function buildComparisonMarkdown(result, targetMonth, baseMonth) {
    const s = result.stats;
    return `# So sánh rebuild Zeus vs legacy\n\n## Period detect\n- Target month: **${targetMonth.label}** (${targetMonth.fromDate} → ${targetMonth.toDate})\n- Base month: **${baseMonth.label}** (${baseMonth.fromDate} → ${baseMonth.toDate})\n\n## Population\n- Legacy rows: **${result.counts.legacy}**\n- Rebuilt rows: **${result.counts.rebuilt}**\n- Common student_id: **${result.counts.common}**\n- Only legacy: **${result.counts.onlyLegacy}**\n- Only rebuilt: **${result.counts.onlyRebuilt}**\n\n## Match summary\n| Field | Exact | Exact % | Within 5 | Within 5 % |
|---|---:|---:|---:|---:|
${Object.entries(s).map(([field, stat]) => `| ${field} | ${stat.exact} | ${stat.exact_pct}% | ${stat.within5} | ${stat.within5_pct}% |`).join('\n')}\n\n## Nhận xét nhanh\n- \`score_target/base/category/movement_group\` là nhóm cột quan trọng nhất để xác định có thể tái tạo legacy-style từ Zeus hay không.\n- Các cột \`activation_speed\`, \`renewal_status\`, \`renewal_product\`, \`lifecycle_status\` hiện đang rebuild bằng heuristic vì legacy gốc trông giống snapshot business rút gọn hơn là raw Zeus truth.\n- \`remaining_sessions\` không đưa vào bảng match chính vì legacy cũ là transformed negative values, không phản ánh balance thật từ Zeus.\n`;
}

async function main() {
    const legacyRows = await loadLegacyRows();
    const legacyCount = legacyRows.length;
    const { best, candidates } = await detectTargetMonth(legacyCount);
    const targetDate = parseMonthLabel(best.label);
    const base = monthRange(previousMonth(targetDate));

    const monthHistory = [];
    for (let cursor = new Date(Date.UTC(2025, 10, 1)); cursor < targetDate; cursor = new Date(Date.UTC(cursor.getUTCFullYear(), cursor.getUTCMonth() + 1, 1))) {
        const range = monthRange(cursor);
        const rows = await getCsiStudents({ fromDate: range.fromDate, toDate: range.toDate }, { sortBy: 'student_id', sortDir: 'asc' });
        monthHistory.push({ ...range, rows, byId: makeRowIndex(rows) });
    }

    const [targetRows, learnerRows] = await Promise.all([
        getCsiStudents({ fromDate: best.fromDate, toDate: best.toDate }, { sortBy: 'student_id', sortDir: 'asc' }),
        listLearnerJourneyStudents(),
    ]);

    const learnerById = makeRowIndex(learnerRows);

    const rebuiltRows = targetRows.map(targetRow => {
        const studentId = String(targetRow.student_id);
        const baselineMonth = monthHistory.find(month => month.byId.has(studentId));
        const baseRow = baselineMonth ? baselineMonth.byId.get(studentId) : null;
        return buildLegacyLikeRow(
            targetRow,
            baseRow,
            learnerById.get(studentId) || null,
            monthHistory.filter(month => month.byId.has(studentId)),
            best,
        );
    });

    const comparison = compareRows(legacyRows, rebuiltRows);
    const runDir = path.resolve(__dirname, '../../../runs/2026-05-22/legacy-zeus-rebuild');
    ensureDir(runDir);

    const payload = {
        detectedTargetMonth: best,
        topMonthCandidates: candidates.slice(0, 6),
        baseMonth: base,
        comparison,
    };

    fs.writeFileSync(path.join(runDir, 'mapping.md'), buildMappingMarkdown(), 'utf8');
    fs.writeFileSync(path.join(runDir, 'comparison.md'), buildComparisonMarkdown(comparison, best, base), 'utf8');
    fs.writeFileSync(path.join(runDir, 'comparison.json'), JSON.stringify(payload, null, 2), 'utf8');
    fs.writeFileSync(path.join(runDir, 'rebuilt_rows_sample.json'), JSON.stringify(rebuiltRows.slice(0, 50), null, 2), 'utf8');

    console.log(JSON.stringify({
        runDir,
        detectedTargetMonth: best,
        baseMonth: base,
        counts: comparison.counts,
        score_target: comparison.stats.score_target,
        score_base: comparison.stats.score_base,
        target_category: comparison.stats.target_category,
        base_category: comparison.stats.base_category,
        movement_group: comparison.stats.movement_group,
    }, null, 2));
}

main().then(() => process.exit(0)).catch(error => {
    console.error('[rebuildLegacyFromZeus] Failed:', error);
    process.exit(1);
});
