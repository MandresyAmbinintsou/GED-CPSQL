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

    if (!$doc || !file_exists($doc['chemin_png'])) {
        http_response_code(404);
        die("Image introuvable");
    }

    $extension = strtolower(pathinfo($doc['chemin_png'], PATHINFO_EXTENSION));
    $mime_types = [
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'tif'  => 'image/tiff',
        'tiff' => 'image/tiff'
    ];

    $content_type = $mime_types[$extension] ?? 'application/octet-stream';
    
    header("Content-Type: $content_type");
    header("Content-Length: " . filesize($doc['chemin_png']));
    readfile($doc['chemin_png']);
} catch (Exception $e) {
    http_response_code(500);
    die("Erreur serveur");
}
