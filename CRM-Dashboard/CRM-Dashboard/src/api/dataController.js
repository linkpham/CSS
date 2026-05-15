// src/api/dataController.js
const { getSheetData } = require('../services/gsheetService');

// Giả sử cần cung cấp endpoint này cho frontend
async function fetchSheetDataEndpoint(req, res) {
    try {
        const spreadsheetId = req.query.id; // ID của google sheet
        if (!spreadsheetId) {
            return res.status(400).json({ error: 'Missing spreadsheetId' });
        }
        const data = await getSheetData(spreadsheetId);
        res.json({ data });
    } catch (error) {
        console.error('Error fetching data:', error);
        res.status(500).json({ error: error.message });
    }
}

module.exports = { fetchSheetDataEndpoint };
