<?php
// api/search.php - Recherche ultra-rapide avec index
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/../config/database.php';

try {
    $term = $_GET['q'] ?? '';
    if (strlen($term) < 2) {
        echo json_encode([]);
        exit;
    }

    $db = Database::getInstance();
    
    // Recherche combinée dans employes et matricules uniques de documents
    $stmt = $db->prepare("
        SELECT matricule, nom, prenom FROM employes 
        WHERE matricule ILIKE :term OR nom ILIKE :term OR prenom ILIKE :term
        UNION
        SELECT DISTINCT matricule, 'Inconnu' as nom, '' as prenom FROM documents 
        WHERE matricule ILIKE :term AND matricule NOT IN (SELECT matricule FROM employes)
        LIMIT 20
    ");

    $stmt->execute(['term' => "%$term%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
