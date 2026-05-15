const { GoogleSpreadsheet } = require('google-spreadsheet');
const { JWT } = require('google-auth-library');
const fs = require('fs');
const path = require('path');

const CREDENTIALS_PATH = path.join(__dirname, '../../../conf/credentials.json');
const SPREADSHEET_ID = '1t46BJhDlgB8BYRlx29x5GeB-Gd3A4Jolzm87OZhYdAQ';

async function listSheetTitles() {
    try {
        const credentials = JSON.parse(fs.readFileSync(CREDENTIALS_PATH, 'utf8'));
        const serviceAccountAuth = new JWT({
            email: credentials.client_email,
            key: credentials.private_key,
            scopes: ['https://www.googleapis.com/auth/spreadsheets.readonly'],
        });

        const doc = new GoogleSpreadsheet(SPREADSHEET_ID, serviceAccountAuth);
        await doc.loadInfo();
        
        console.log('Available sheet titles:', doc.sheetsByIndex.map(s => s.title));
    } catch (error) {
        console.error('Error:', error);
    }
}

listSheetTitles();
