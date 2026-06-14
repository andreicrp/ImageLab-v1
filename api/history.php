<?php

require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/HistoryManager.php';

// Allow only GET requests for history/analytics data
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Invalid request method. GET required.', 405);
}

$historyManager = new HistoryManager();

$analytics = $historyManager->getAnalytics();
$recentLogs = $historyManager->getHistory(10);

Response::success([
    'analytics' => $analytics,
    'recent_logs' => $recentLogs
], 'Analytics and transaction history fetched successfully.');
