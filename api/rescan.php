<?php
// api/rescan.php - Lance un rescan complet
header('Content-Type: application/json');

require_once __DIR__ . '/scan_helpers.php';

try {
    $dossier_archives = __DIR__ . '/../archives';
    $base_dir = realpath(__DIR__ . '/..');
    $scanner_path = findScannerPath($base_dir);

    if (!$scanner_path) {
        http_response_code(500);
        echo json_encode(['error' => 'Scanner non disponible']);
        exit;
    }

    $command = buildScannerCommand($scanner_path, $dossier_archives);
    if (isWindows()) {
        runDetachedCommand($command);
        echo json_encode(['status' => 'ok', 'message' => 'Scan lancé en arrière-plan sous Windows.']);
        exit;
    }

    exec($command . ' 2>&1', $output, $return_var);

    if ($return_var === 0) {
        echo json_encode(['status' => 'ok', 'message' => 'Scan terminé', 'output' => $output]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur lors du scan', 'output' => $output]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
