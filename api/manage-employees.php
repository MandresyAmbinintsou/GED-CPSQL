<?php
// api/manage-employees.php - Gestion des employés
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Liste tous les employés
    try {
        $stmt = $db->query("SELECT matricule, nom, prenom, actif, created_at FROM employes ORDER BY matricule");
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($employees);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ajouter un employé
    $matricule = trim($_POST['matricule'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    
    if (empty($matricule)) {
        http_response_code(400);
        echo json_encode(['error' => 'Le matricule est requis']);
        exit;
    }
    
    try {
        $stmt = $db->prepare("
            INSERT INTO employes (matricule, nom, prenom) 
            VALUES (:matricule, :nom, :prenom)
            ON CONFLICT (matricule) DO UPDATE SET 
                nom = EXCLUDED.nom, 
                prenom = EXCLUDED.prenom
        ");
        $stmt->execute([
            'matricule' => $matricule,
            'nom' => $nom ?: null,
            'prenom' => $prenom ?: null
        ]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Supprimer un employé
    $input = json_decode(file_get_contents('php://input'), true);
    $matricule = $input['matricule'] ?? '';
    
    if (empty($matricule)) {
        http_response_code(400);
        echo json_encode(['error' => 'Le matricule est requis']);
        exit;
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM employes WHERE matricule = :matricule");
        $stmt->execute(['matricule' => $matricule]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
}
?>