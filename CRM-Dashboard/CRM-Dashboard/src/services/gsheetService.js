// src/services/gsheetService.js
const { GoogleSpreadsheet } = require('google-spreadsheet');
const { JWT } = require('google-auth-library');
const fs = require('fs');
const path = require('path');

const CREDENTIALS_PATH = path.join(__dirname, '../../../conf/credentials.json');

function parseNumber(value) {
    if (value === null || value === undefined || value === '') return 0;
    const normalized = String(value).replace(/,/g, '').replace(/%/g, '').trim();
    const parsed = Number(normalized);
    return Number.isFinite(parsed) ? parsed : 0;
}

function parsePercent(value) {
    if (value === null || value === undefined || value === '') return 0;
    const n = parseNumber(value);
    return String(value).includes('%') ? n / 100 : n;
}

function mapDataModelRow(row) {
    const r = row.toObject();
    return {
        period: {
            date: r['Cut off date'] || r['Ngày'] || r['Date'] || '',
            month: r['Period_Month'] || r['Tháng'] || r['Month'] || '',
            quarter: r['Period_Quarter'] || r['Quý'] || r['Quarter'] || '',
            year: r['Period_Year'] || r['Năm'] || r['Year'] || '',
            week: r['Period_Week'] || r['Tuần'] || r['Week'] || '',
        },
        student: {
            id: String(r['Student_ID'] || '').trim(),
            name: r['Tên Học viên'] || '',
            email: r['Email'] || '',
            phone: r['SĐT'] || '',
            css: r['CSS'] || '',
        },
        health: {
            scoreTarget: parseNumber(r['Score_Target']),
            scoreBase: parseNumber(r['Score_Base']),
            variance: parseNumber(r['MoM/QoQ_Variance']),
            targetCategory: r['Phân loại Target'] || '',
            baseCategory: r['Phân loại Base'] || '',
            managementScore: parseNumber(r['Điểm sức khỏe quản trị']),
            learningPace: parseNumber(r['Nhịp độ học tập']),
        },
        movement: {
            group: r['Nhóm'] || '',
        },
        operation: {
            teacherDisruptionRate: parsePercent(r['Tỉ lệ gián đoạn do GV']),
            unfinishedRate: parsePercent(r['Tỉ lệ học dở']),
            activationSpeed: r['Tốc độ kích hoạt'] || '',
            teacherDisruptionCumulative: parsePercent(r['Gián đoạn do GV  (tích lũy)']),
        },
        renewal: {
            status: r['Trạng thái gia hạn'] || '',
            revenue: parseNumber(r['Doanh thu gia hạn']),
            product: r['Sản phẩm gia hạn chi tiết'] || '',
            remainingSessions: parseNumber(r['Số buổi còn lại']),
            lifecycleStatus: r['Trạng thái vòng đời'] || '',
        },
    };
}

async function getSheetData(spreadsheetId, sheetName = 'Data_Model') {
    if (!fs.existsSync(CREDENTIALS_PATH)) {
        throw new Error('Credentials file not found at ' + CREDENTIALS_PATH);
    }
    const credentials = JSON.parse(fs.readFileSync(CREDENTIALS_PATH, 'utf8'));
    const serviceAccountAuth = new JWT({
        email: credentials.client_email,
        key: credentials.private_key,
        scopes: ['https://www.googleapis.com/auth/spreadsheets.readonly'],
    });

    const doc = new GoogleSpreadsheet(spreadsheetId, serviceAccountAuth);
    await doc.loadInfo();

    const sheet = doc.sheetsByTitle[sheetName];
    if (!sheet) {
        throw new Error(`Sheet with title '${sheetName}' not found. Available: ${doc.sheetsByIndex.map(s => s.title).join(', ')}`);
    }

    const rows = await sheet.getRows();
    return rows.map(mapDataModelRow).filter(item => item.student.id);
}

module.exports = { getSheetData, parseNumber, parsePercent };
