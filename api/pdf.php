<?php
// pdf.php - Génération de PDF
require_once 'config/database.php';
require_once __DIR__ . '/api/scan_helpers.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo "ID manquant";
    exit;
}

try {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT chemin_png, nom_fichier, matricule, type_dossier_nom FROM documents WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        http_response_code(404);
        die("Document introuvable en base de données.");
    }

    $chemin_png = $doc['chemin_png'];
    $nom_fichier = $doc['nom_fichier'];

    if (!file_exists($chemin_png)) {
        $stmt_base = $db->query('SELECT chemin FROM scan_history ORDER BY dernier_scan DESC LIMIT 1');
        $basePath = $stmt_base->fetchColumn();
        if ($basePath) {
            $basePath = rtrim(str_replace('\\', '/', $basePath), '/');
            $candidate = $basePath . '/' . ltrim(str_replace('\\', '/', $chemin_png), '/');
            if (file_exists($candidate)) {
                $chemin_png = $candidate;
            }
        }
    }

    if (!file_exists($chemin_png)) {
        http_response_code(404);
        die("Fichier source image introuvable sur le disque : " . htmlspecialchars($chemin_png));
    }

    $cache_dir = 'cache_pdf';
    if (!is_dir($cache_dir) && !mkdir($cache_dir, 0755, true)) {
        die("Impossible de créer le dossier cache_pdf. Vérifiez les permissions.");
    }

    $nom_base = pathinfo($nom_fichier, PATHINFO_FILENAME);
    $chemin_pdf = $cache_dir . DIRECTORY_SEPARATOR . $nom_base . '_' . $id . '.pdf';

    $besoin_conversion = !file_exists($chemin_pdf);
    if (!$besoin_conversion) {
        $date_png = filemtime($chemin_png);
        $date_pdf = filemtime($chemin_pdf);
        $besoin_conversion = $date_png > $date_pdf;
    }

    if ($besoin_conversion) {
        if (commandExists('img2pdf')) {
            $command = 'img2pdf ' . escapeshellarg($chemin_png) . ' -o ' . escapeshellarg($chemin_pdf) . ' 2>&1';
            exec($command, $output, $return_var);
            if ($return_var !== 0) {
                http_response_code(500);
                echo "Erreur lors de la conversion PDF :<br>";
                echo "<pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
                exit;
            }
        } elseif (class_exists('Imagick')) {
            $imagick = new Imagick();
            $imagick->readImage($chemin_png);
            $imagick->setImageFormat('pdf');
            $imagick->writeImage($chemin_pdf);
            $imagick->clear();
            $imagick->destroy();
        } elseif (commandExists('magick')) {
            $command = 'magick convert ' . escapeshellarg($chemin_png) . ' ' . escapeshellarg($chemin_pdf) . ' 2>&1';
            exec($command, $output, $return_var);
            if ($return_var !== 0) {
                http_response_code(500);
                echo "Erreur lors de la conversion PDF :<br>";
                echo "<pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
                exit;
            }
        } else {
            die("Aucun outil de conversion PDF disponible. Installez 'img2pdf', ImageMagick ou utilisez l'aperçu image.");
        }

        $update_stmt = $db->prepare("UPDATE documents SET chemin_pdf = ?, est_converti = true WHERE id = ?");
        $update_stmt->execute([$chemin_pdf, $id]);
    }

    if (!file_exists($chemin_pdf)) {
        die("Le fichier PDF n'a pas été généré.");
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($doc['matricule'] . '_' . $doc['type_dossier_nom'] . '_' . $nom_base . '.pdf') . '"');
    readfile($chemin_pdf);

} catch (Exception $e) {
    http_response_code(500);
    die("Erreur système : " . $e->getMessage());
}
