<?php
// api/view-image.php - Affichage sécurisé de l'image originale
require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    http_response_code(400);
    die("ID manquant");
}

try {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT chemin_png FROM documents WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$doc) {
        http_response_code(404);
        die("Image introuvable");
    }

    $imagePath = $doc['chemin_png'];
    if (!file_exists($imagePath)) {
        $stmt_base = $db->query('SELECT chemin FROM scan_history ORDER BY dernier_scan DESC LIMIT 1');
        $basePath = $stmt_base->fetchColumn();
        if ($basePath) {
            $basePath = rtrim(str_replace('\\', '/', $basePath), '/');
            $candidate = $basePath . '/' . ltrim(str_replace('\\', '/', $imagePath), '/');
            if (file_exists($candidate)) {
                $imagePath = $candidate;
            }
        }
    }

    if (!file_exists($imagePath)) {
        http_response_code(404);
        die("Image introuvable");
    }

    $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
    $mime_types = [
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'tif'  => 'image/tiff',
        'tiff' => 'image/tiff'
    ];

    $content_type = $mime_types[$extension] ?? 'application/octet-stream';
    
    header("Content-Type: $content_type");
    header("Content-Length: " . filesize($imagePath));
    readfile($imagePath);
} catch (Exception $e) {
    http_response_code(500);
    die("Erreur serveur");
}
