<?php
// api/matricule.php - Documents d'un employé (scan en direct au F5)
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../config/database.php';

try {
    $matricule = $_GET['matricule'] ?? '';
    if (!$matricule) {
        echo json_encode([]);
        exit;
    }

    $db = Database::getInstance();
    
    // 1. Récupérer le chemin de base des archives (externe au projet)
    $stmt_path = $db->query('SELECT chemin FROM scan_history ORDER BY dernier_scan DESC LIMIT 1');
    $base_archives_path = $stmt_path->fetchColumn();

    if ($base_archives_path) {
        $base_archives_path = rtrim(str_replace('\\', '/', $base_archives_path), '/');
        $employee_dir = $base_archives_path . '/' . $matricule;

        // --- SYNCHRONISATION EN TEMPS RÉEL (F5) ---
        if (is_dir($employee_dir)) {
            clearstatcache(true, $employee_dir);
            
            $fichiers_reels = [];
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($employee_dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && in_array(strtolower($file->getExtension()), ['png', 'jpg', 'jpeg'])) {
                    $full_path = str_replace('\\', '/', $file->getRealPath());
                    
                    // Extraction du type de dossier (nom du dossier parent direct du fichier)
                    $path_parts = explode('/', $full_path);
                    $count = count($path_parts);
                    $type_dossier = ($count >= 2) ? $path_parts[$count - 2] : 'Autres';
                    
                    // Le chemin relatif pour l'affichage (depuis la base des archives)
                    $relative_path = $matricule . '/' . $type_dossier . '/' . $file->getBasename();
                    
                    $fichiers_reels[] = [
                        'chemin' => $relative_path,
                        'nom' => $file->getBasename(),
                        'taille' => $file->getSize(),
                        'type' => $type_dossier
                    ];
                }
            }

            // Mise à jour atomique de la DB pour ce matricule
            $db->beginTransaction();
            $db->prepare("DELETE FROM documents WHERE matricule = :m")->execute(['m' => $matricule]);

            $stmt_ins = $db->prepare("
                INSERT INTO documents (matricule, type_dossier_nom, nom_fichier, chemin_png, taille_bytes, date_scan)
                VALUES (:m, :t, :n, :c, :s, NOW())
            ");

            foreach ($fichiers_reels as $f) {
                $stmt_ins->execute([
                    'm' => $matricule,
                    't' => $f['type'],
                    'n' => $f['nom'],
                    'c' => $f['chemin'],
                    's' => $f['taille']
                ]);
            }
            $db->commit();
        }
    }
    // --- FIN SYNCHRONISATION ---

    $stmt = $db->prepare("
        SELECT type_dossier_nom, nom_fichier, chemin_png, taille_bytes, date_scan, id
        FROM documents
        WHERE matricule = :matricule
        ORDER BY type_dossier_nom, nom_fichier
    ");

    $stmt->execute(['matricule' => $matricule]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organisation par type de dossier
    $dossiers = [];
    foreach ($documents as $doc) {
        $type = $doc['type_dossier_nom'];
        if (!isset($dossiers[$type])) {
            $dossiers[$type] = [];
        }
        $dossiers[$type][] = $doc;
    }

    echo json_encode($dossiers);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
