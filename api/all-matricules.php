<?php
// api/all-matricules.php - Liste tous les matricules avec détection automatique
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../compat.php';

try {
    $db = Database::getInstance();

    // 1. Récupérer le chemin de base des archives
    $stmt_path = $db->query('SELECT chemin FROM scan_history ORDER BY dernier_scan DESC LIMIT 1');
    $base_archives_path = $stmt_path->fetchColumn();

    // 2. Détection automatique des nouveaux employés (F5)
    if ($base_archives_path && is_dir($base_archives_path)) {
        clearstatcache(true, $base_archives_path);

        $iterator = new DirectoryIterator($base_archives_path);
        $existing_matricules = [];

        // Récupérer les matricules existants
        $stmt_existing = $db->query('SELECT matricule FROM employes');
        while ($row = $stmt_existing->fetch(PDO::FETCH_ASSOC)) {
            $existing_matricules[$row['matricule']] = true;
        }

        // Scanner les sous-dossiers pour détecter les nouveaux employés
        foreach ($iterator as $item) {
            if ($item->isDir() && !$item->isDot()) {
                $folder_name = $item->getFilename();

                // Vérifier si ce dossier contient des images (c'est un employé)
                $has_images = false;
                $folder_path = $item->getRealPath();

                if (is_dir($folder_path)) {
                    try {
                        $sub_iterator = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($folder_path, RecursiveDirectoryIterator::SKIP_DOTS),
                            RecursiveIteratorIterator::LEAVES_ONLY
                        );

                        // Limiter la profondeur pour éviter les scans trop longs
                        $sub_iterator->setMaxDepth(3);

                        foreach ($sub_iterator as $file) {
                            if ($file->isFile() && isImageFile($file->getFilename())) {
                                $has_images = true;
                                break;
                            }
                        }
                    } catch (Exception $e) {
                        // Ignorer les erreurs d'accès aux dossiers
                        continue;
                    }
                }

                // Si c'est un nouveau matricule avec des images, l'ajouter
                if ($has_images && !isset($existing_matricules[$folder_name])) {
                    try {
                        $stmt_insert = $db->prepare('INSERT INTO employes (matricule) VALUES (:matricule)');
                        $stmt_insert->execute(['matricule' => $folder_name]);
                        $existing_matricules[$folder_name] = true; // Marquer comme ajouté
                    } catch (Exception $e) {
                        // Ignorer les erreurs de clé dupliquée ou autres
                        error_log("Erreur ajout employé $folder_name: " . $e->getMessage());
                    }
                }
            }
        }
    }

    // 3. Retourner tous les matricules
    $stmt = $db->query("
        SELECT DISTINCT matricule FROM documents
        UNION
        SELECT matricule FROM employes
        ORDER BY matricule
    ");
    $matricules = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($matricules);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
