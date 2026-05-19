const { GoogleSpreadsheet } = require('google-spreadsheet');
const { JWT } = require('google-auth-library');
const fs = require('fs');
const path = require('path');

const CREDENTIALS_PATH = path.join(__dirname, '../conf/credentials.json');
const SPREADSHEET_ID = '1CHXlpCmanz8BxvPg91898g_aFoMKKIWx-16n0SyM3wY';

async function testConnection() {
    try {
        if (!fs.existsSync(CREDENTIALS_PATH)) {
            console.error('Credentials file not found at ' + CREDENTIALS_PATH);
            return;
        }
        const credentials = JSON.parse(fs.readFileSync(CREDENTIALS_PATH, 'utf8'));

        // Cập nhật phương thức xác thực cho version 5.x
        const serviceAccountAuth = new JWT({
            email: credentials.client_email,
            key: credentials.private_key,
            scopes: [
                'https://www.googleapis.com/auth/spreadsheets',
            ],
        });

        const doc = new GoogleSpreadsheet(SPREADSHEET_ID, serviceAccountAuth);
        
        await doc.loadInfo();
        console.log('Successfully connected!');
        console.log('Sheet Title:', doc.title);
        
        const sheet = doc.sheetsByIndex[0];
        await sheet.loadHeaderRow();
        console.log('Headers:', sheet.headerValues);
    } catch (error) {
        console.error('Error connecting to sheet:', error.message);
    }
}

testConnection();
