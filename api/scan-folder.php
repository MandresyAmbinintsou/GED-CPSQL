<?php
// api/scan-folder.php - API pour scanner un dossier spécifique
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

require_once __DIR__ . '/scan_helpers.php';

$input = json_decode(file_get_contents('php://input'), true);
$folder_path = trim($input['folder_path'] ?? '');
$immediate = !empty($input['immediate']);

if (!$folder_path) {
    http_response_code(400);
    echo json_encode(['error' => 'Chemin du dossier non spécifié']);
    exit;
}

$base_dir = realpath(__DIR__ . '/..');
$target_path = resolveTargetPath($folder_path, $base_dir);

if (!$target_path || !is_dir($target_path)) {
    http_response_code(400);
    echo json_encode(['error' => 'Le dossier n\'existe pas ou n\'est pas accessible']);
    exit;
}

try {
    require_once __DIR__ . '/../config/database.php';
    $db = Database::getInstance();

    $scanner_path = findScannerPath($base_dir);
    $scan_completed = false;
    $message = '';

    if ($scanner_path && !$immediate) {
        $command = buildScannerCommand($scanner_path, $target_path);
        runDetachedCommand($command);
        $message = isWindows()
            ? 'Scan lancé en arrière-plan avec le scanner Windows.'
            : 'Scan lancé en arrière-plan.';
    } else {
        set_time_limit(0);
        ini_set('max_execution_time', '0');

        if ($scanner_path) {
            $command = buildScannerCommand($scanner_path, $target_path);
            exec($command . ' 2>&1', $output, $return_var);
            if ($return_var !== 0) {
                throw new RuntimeException('Erreur lors du scan synchronisé : ' . implode("\n", $output));
            }
            $message = 'Scan synchronisé terminé.';
        } else {
            scanFolderWithPhp($target_path, $db);
            $message = 'Scan exécuté en PHP (compatibilité Windows / scanner absent).';
        }

        $scan_completed = true;
    }

    cleanupMissingDocuments($db);
    ensureScanHistoryTable($db);
    recordScanHistory($db, $target_path);

    $documents_count = (int)$db->query('SELECT COUNT(*) FROM documents')->fetchColumn();
    $matricules = $db->query('SELECT DISTINCT matricule FROM employes ORDER BY matricule')->fetchAll(PDO::FETCH_COLUMN);
    $folders = $db->query(
        "SELECT DISTINCT split_part(chemin_png, '/', 1) || '/' || split_part(chemin_png, '/', 2) as folder_structure "
        . "FROM documents WHERE chemin_png LIKE '%/%' ORDER BY folder_structure"
    )->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'status' => 'ok',
        'message' => $message,
        'documents_count' => $documents_count,
        'matricules' => $matricules,
        'folders' => $folders,
        'scan_completed' => $scan_completed
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors du scan : ' . $e->getMessage()]);
}

function scanFolderWithPhp(string $basePath, PDO $db): void {
    $validMatricules = loadValidMatricules($db);
    $insertStmt = $db->prepare(
        'INSERT INTO documents (matricule, type_dossier_nom, nom_fichier, chemin_png, taille_bytes, hash_md5, date_scan) '
        . 'VALUES (:matricule, :type_dossier_nom, :nom_fichier, :chemin_png, :taille_bytes, :hash_md5, NOW()) '
        . 'ON CONFLICT (chemin_png) DO UPDATE SET matricule = EXCLUDED.matricule, '
        . 'type_dossier_nom = EXCLUDED.type_dossier_nom, nom_fichier = EXCLUDED.nom_fichier, '
        . 'taille_bytes = EXCLUDED.taille_bytes, hash_md5 = EXCLUDED.hash_md5, date_scan = NOW()'
    );

    $db->beginTransaction();
    try {
        $realBase = realpath($basePath);
        if ($realBase === false) {
            throw new RuntimeException('Impossible de résoudre le chemin de base.');
        }

        $baseName = pathinfo($realBase, PATHINFO_BASENAME);
        if (isset($validMatricules[$baseName])) {
            scanMatriculeDir($realBase, $baseName, $insertStmt);
        } else {
            $iterator = new DirectoryIterator($realBase);
            foreach ($iterator as $entry) {
                if (!$entry->isDir() || $entry->isDot()) {
                    continue;
                }
                $name = $entry->getFilename();
                if (isset($validMatricules[$name])) {
                    scanMatriculeDir($entry->getRealPath(), $name, $insertStmt);
                }
            }
        }

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

function loadValidMatricules(PDO $db): array {
    $stmt = $db->query('SELECT matricule FROM employes');
    $matricules = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $matricules[$row['matricule']] = true;
    }
    return $matricules;
}

function scanMatriculeDir(string $path, string $matricule, PDOStatement $insertStmt): void {
    $iterator = new DirectoryIterator($path);
    foreach ($iterator as $entry) {
        if ($entry->isDot()) {
            continue;
        }

        $childPath = $entry->getRealPath();
        if ($entry->isDir()) {
            scanTypeDir($childPath, $matricule, $entry->getFilename(), $insertStmt);
            continue;
        }

        if ($entry->isFile() && isImageFile($entry->getFilename())) {
            insertDocument($insertStmt, $matricule, '', $childPath, $entry->getFilename());
        }
    }
}

function scanTypeDir(string $path, string $matricule, string $typeDossier, PDOStatement $insertStmt): void {
    $iterator = new DirectoryIterator($path);
    foreach ($iterator as $entry) {
        if ($entry->isDot()) {
            continue;
        }

        $childPath = $entry->getRealPath();
        if ($entry->isDir()) {
            scanTypeDir($childPath, $matricule, $typeDossier, $insertStmt);
            continue;
        }

        if ($entry->isFile() && isImageFile($entry->getFilename())) {
            insertDocument($insertStmt, $matricule, $typeDossier, $childPath, $entry->getFilename());
        }
    }
}

function insertDocument(PDOStatement $stmt, string $matricule, string $typeDossier, string $chemin, string $nomFichier): void {
    $chemin = normalizePathForDatabase($chemin);
    $hash = @hash_file('md5', $chemin);
    $taille = @filesize($chemin);

    if ($hash === false || $taille === false) {
        return;
    }

    try {
        $stmt->execute([
            ':matricule' => $matricule,
            ':type_dossier_nom' => $typeDossier,
            ':nom_fichier' => $nomFichier,
            ':chemin_png' => $chemin,
            ':taille_bytes' => $taille,
            ':hash_md5' => $hash,
        ]);
    } catch (PDOException $e) {
        // Ignorer les erreurs liées aux clés étrangères ou aux accès concurrents.
    }
}

function cleanupMissingDocuments(PDO $db): void {
    $stmt_check = $db->query('SELECT id, chemin_png FROM documents');
    $to_delete = [];
    while ($row = $stmt_check->fetch(PDO::FETCH_ASSOC)) {
        if (!file_exists($row['chemin_png'])) {
            $to_delete[] = $row['id'];
        }
    }

    if (!empty($to_delete)) {
        $db->exec('DELETE FROM documents WHERE id IN (' . implode(',', $to_delete) . ')');
    }
}

function ensureScanHistoryTable(PDO $db): void {
    $db->exec(
        'CREATE TABLE IF NOT EXISTS scan_history ('
        . 'chemin TEXT PRIMARY KEY, '
        . 'dernier_scan TIMESTAMP NOT NULL'
        . ')'
    );
}

function recordScanHistory(PDO $db, string $target_path): void {
    $stmt_hist = $db->prepare(
        'INSERT INTO scan_history (chemin, dernier_scan) VALUES (:path, NOW()) '
        . 'ON CONFLICT (chemin) DO UPDATE SET dernier_scan = NOW()'
    );
    $stmt_hist->execute(['path' => $target_path]);
}
