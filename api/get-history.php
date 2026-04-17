<?php
// api/get-history.php - Historique des dossiers scannés
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();
    $db->exec(
        'CREATE TABLE IF NOT EXISTS scan_history ('
        . 'chemin TEXT PRIMARY KEY, '
        . 'dernier_scan TIMESTAMP NOT NULL'
        . ')'
    );
    $stmt = $db->query("SELECT chemin, dernier_scan FROM scan_history ORDER BY dernier_scan DESC LIMIT 10");
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($history);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
