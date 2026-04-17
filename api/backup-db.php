<?php
// api/backup-db.php - Sauvegarde de la base de données
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();
    $backup_dir = __DIR__ . '/../backups';
    
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0777, true);
    }

    $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = $backup_dir . '/' . $filename;

    // Pour un projet portable Windows, on utilise une redirection simple si pg_dump n'est pas dispo
    // Sinon on peut tenter un dump SQL basique via PDO pour la portabilité maximale
    $tables = ['employes', 'types_dossiers', 'documents', 'scan_history'];
    $sql_dump = "-- Sauvegarde automatique " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($tables as $table) {
        $res = $db->query("SELECT * FROM $table");
        $rows = $res->fetchAll(PDO::FETCH_ASSOC);
        
        $sql_dump .= "--- Table: $table ---\n";
        foreach ($rows as $row) {
            $keys = array_keys($row);
            $values = array_map(function($v) use ($db) {
                if ($v === null) return 'NULL';
                return $db->quote($v);
            }, array_values($row));
            
            $sql_dump .= "INSERT INTO $table (" . implode(',', $keys) . ") VALUES (" . implode(',', $values) . ");\n";
        }
        $sql_dump .= "\n";
    }

    file_put_contents($filepath, $sql_dump);

    echo json_encode([
        'success' => true, 
        'message' => 'Sauvegarde effectuée avec succès',
        'file' => $filename
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
