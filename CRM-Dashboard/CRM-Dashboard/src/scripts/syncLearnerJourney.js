const { syncLearnerJourneyData } = require('../services/learnerJourneyService');

async function main() {
    const source = process.env.LEARNER_JOURNEY_SOURCE || 'json';
    const jsonPath = process.env.LEARNER_JOURNEY_JSON_PATH || '';
    console.log(`[learner-journey-sync] Starting source=${source}${jsonPath ? ` json=${jsonPath}` : ''}`);
    const result = await syncLearnerJourneyData({ source, jsonPath });
    console.log(`[learner-journey-sync] Completed: ${result.rows} students at ${result.syncedAt}`);
}

if (require.main === module) {
    main()
        .then(() => process.exit(0))
        .catch(error => {
            console.error('[learner-journey-sync] Failed:', error);
            process.exit(1);
        });
}

module.exports = { main };
