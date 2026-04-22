<?php
// helpers.php - Fonctions utilitaires pour la compatibilité multiplateforme

/**
 * Normalise un chemin pour la base de données (convertit les \ en /)
 */
function normalizePathForDatabase($path) {
    return str_replace(['\\'], '/', $path);
}

/**
 * Normalise un chemin selon le système d'exploitation
 */
function normalizePath($path) {
    return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
}

/**
 * Vérifie si un chemin est absolu (compatible Windows et Unix)
 */
function isAbsolutePath($path) {
    if (!$path) {
        return false;
    }

    if ($path[0] === '/' || $path[0] === '\\') {
        return true;
    }

    // Windows: C:\ ou C:/
    return preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
}

/**
 * Résout un chemin relatif vers un chemin absolu
 */
function resolveTargetPath($folder_path, $base_dir) {
    $folder_path = trim($folder_path);
    $folder_path = normalizePath($folder_path);

    if (isAbsolutePath($folder_path)) {
        return realpath($folder_path);
    }

    return realpath($base_dir . DIRECTORY_SEPARATOR . $folder_path);
}

/**
 * Vérifie si un fichier est une image
 */
function isImageFile($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'bmp']);
}

/**
 * Détecte si on est sur Windows
 */
function isWindows() {
    return strncasecmp(PHP_OS, 'WIN', 3) === 0;
}

/**
 * Obtient le séparateur de chemin approprié
 */
function getPathSeparator() {
    return isWindows() ? '\\' : '/';
}

/**
 * Convertit les chemins relatifs en chemins absolus dans les données JSON
 */
function normalizePathsInData($data, $base_path) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            if (is_string($value) && (strpos($value, '/') !== false || strpos($value, '\\') !== false)) {
                // Si c'est un chemin relatif, le convertir en absolu
                if (!isAbsolutePath($value)) {
                    $data[$key] = normalizePathForDatabase(realpath($base_path . DIRECTORY_SEPARATOR . $value));
                } else {
                    $data[$key] = normalizePathForDatabase($value);
                }
            } elseif (is_array($value)) {
                $data[$key] = normalizePathsInData($value, $base_path);
            }
        }
    }
    return $data;
}
?>