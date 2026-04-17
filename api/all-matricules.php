<?php
// api/all-matricules.php - Liste tous les matricules
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();
    $stmt = $db->query("
        SELECT DISTINCT matricule FROM documents 
        ORDER BY matricule
    ");
    $matricules = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($matricules);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
