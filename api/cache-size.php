<?php
// api/cache-size.php - Taille du cache PDF
header('Content-Type: application/json');

$cache_dir = __DIR__ . '/../cache_pdf';
$taille_totale = 0;

if (is_dir($cache_dir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cache_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if ($file->isFile()) {
            $taille_totale += $file->getSize();
        }
    }
}

if ($taille_totale < 1024) {
    $taille_str = $taille_totale . ' o';
} elseif ($taille_totale < 1024 * 1024) {
    $taille_str = round($taille_totale / 1024, 1) . ' Ko';
} else {
    $taille_str = round($taille_totale / (1024 * 1024), 1) . ' Mo';
}

echo json_encode([
    'taille_bytes' => $taille_totale,
    'taille_formatee' => $taille_str
]);
