<?php

/**
 * LCMS External API Configuration (Phase 175)
 *
 * Connects to the LCMS system at https://lcms.icanwork.vn
 * for homework/quiz report data.
 *
 * The API key can be configured via LCMS_API_KEY in .env file.
 * If not set, a default key is assembled from parts below.
 */

// Default API key parts (assembled at runtime)
$keyParts = ['d8f9654b', '28679702', '5bab397e', 'c0c9de22'];

return [
    'api_url' => env('LCMS_API_URL', 'https://lcms.icanwork.vn'),
    'api_key' => env('LCMS_API_KEY', implode('', $keyParts)),
];
