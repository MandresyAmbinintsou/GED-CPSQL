<?php
// api/list-directories.php - API pour lister les dossiers sur le serveur
header('Content-Type: application/json');
require_once __DIR__ . '/scan_helpers.php';

$path = $_GET['path'] ?? '';
$path = trim($path);
$base_dir = realpath(__DIR__ . '/..');

if ($path !== '') {
    $path = normalizePath($path);
}

if ($path === '') {
    if (!isWindows()) {
        $path = $base_dir;
    } else {
        $path = null;
    }
} elseif (!is_dir($path)) {
    if (isWindows() && isAbsolutePath($path)) {
        $alternate = normalizePath(str_replace('/', '\\', $path));
        if (is_dir($alternate)) {
            $path = $alternate;
        }
    }

    if ($path !== null && !is_dir($path)) {
        $parent = dirname($path);
        if ($parent !== $path && is_dir($parent)) {
            $path = $parent;
        } elseif (!isWindows()) {
            $path = $base_dir;
        } else {
            $path = null;
        }
    }
}

$results = [];

try {
    if ($path === null) {
        $results = listWindowsDrives();
        echo json_encode([
            'current_path' => '',
            'parent_path' => '',
            'directories' => $results
        ]);
        exit;
    }

    $iterator = new DirectoryIterator($path);
    foreach ($iterator as $fileinfo) {
        if ($fileinfo->isDir() && !$fileinfo->isDot()) {
            $results[] = [
                'name' => $fileinfo->getFilename(),
                'path' => $fileinfo->getRealPath(),
                'type' => 'dir'
            ];
        }
    }
    
    // Trier par nom
    usort($results, function($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });

    echo json_encode([
        'current_path' => realpath($path),
        'parent_path' => dirname(realpath($path)),
        'directories' => $results
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function listWindowsDrives(): array {
    $drives = [];
    foreach (range('A', 'Z') as $letter) {
        $root = $letter . ':\\';
        if (is_dir($root)) {
            $drives[] = [
                'name' => $root,
                'path' => $root,
                'type' => 'dir'
            ];
        }
    }
    return $drives;
}
