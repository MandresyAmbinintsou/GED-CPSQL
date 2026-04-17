<?php
// api/last-scan-path.php - Dernier chemin scanné
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();
    $db->exec(
        'CREATE TABLE IF NOT EXISTS scan_history ('
        . 'chemin TEXT PRIMARY KEY, '
        . 'dernier_scan TIMESTAMP NOT NULL'
        . ')'
    );

    $stmt = $db->query('SELECT chemin FROM scan_history ORDER BY dernier_scan DESC LIMIT 1');
    $path = $stmt->fetchColumn();

    echo json_encode(['path' => $path ?: null]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
