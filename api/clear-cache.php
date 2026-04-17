<?php
// api/clear-cache.php - Vider le cache PDF
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

$cache_dir = __DIR__ . '/../cache_pdf';
$fichiers_supprimes = 0;
$espace_total = 0;

if (is_dir($cache_dir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cache_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if ($file->isFile()) {
            $espace_total += $file->getSize();
            unlink($file->getRealPath());
            $fichiers_supprimes++;
        }
    }

    $dirs = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cache_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($dirs as $dir) {
        if ($dir->isDir()) {
            @rmdir($dir->getRealPath());
        }
    }
}

if ($espace_total < 1024) {
    $espace_str = $espace_total . ' o';
} elseif ($espace_total < 1024 * 1024) {
    $espace_str = round($espace_total / 1024, 1) . ' Ko';
} else {
    $espace_str = round($espace_total / (1024 * 1024), 1) . ' Mo';
}

echo json_encode([
    'fichiers_supprimes' => $fichiers_supprimes,
    'espace_liberte' => $espace_str
]);
