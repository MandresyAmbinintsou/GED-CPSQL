<?php
// compat.php - Fonctions de compatibilité pour Windows/XAMPP

// Fonction de compatibilité pour realpath sur Windows
if (!function_exists('realpath_windows')) {
    function realpath_windows($path) {
        // Sur Windows, realpath peut avoir des problèmes avec les chemins UNC
        if (isWindows()) {
            // Convertir les chemins relatifs en absolus
            if (!isAbsolutePath($path)) {
                $path = getcwd() . DIRECTORY_SEPARATOR . $path;
            }

            // Normaliser les séparateurs
            $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

            // Résoudre . et ..
            $parts = explode(DIRECTORY_SEPARATOR, $path);
            $resolved = [];

            foreach ($parts as $part) {
                if ($part === '' || $part === '.') {
                    continue;
                }
                if ($part === '..') {
                    array_pop($resolved);
                } else {
                    $resolved[] = $part;
                }
            }

            $resolved_path = implode(DIRECTORY_SEPARATOR, $resolved);

            // Ajouter le lecteur si nécessaire
            if (preg_match('/^[A-Za-z]:/', $path)) {
                return $resolved_path;
            }

            return DIRECTORY_SEPARATOR . $resolved_path;
        }

        return realpath($path);
    }
}

// Fonction de compatibilité pour file_exists sur les chemins longs Windows
if (!function_exists('file_exists_windows')) {
    function file_exists_windows($path) {
        if (isWindows() && strlen($path) > 260) {
            // Pour les chemins longs sur Windows, utiliser un préfixe spécial
            $path = '\\\\?\\' . str_replace('/', '\\', $path);
        }
        return file_exists($path);
    }
}

// Fonction de compatibilité pour is_dir
if (!function_exists('is_dir_windows')) {
    function is_dir_windows($path) {
        if (isWindows() && strlen($path) > 260) {
            $path = '\\\\?\\' . str_replace('/', '\\', $path);
        }
        return is_dir($path);
    }
}

// Fonction de compatibilité pour opendir
if (!function_exists('opendir_windows')) {
    function opendir_windows($path) {
        if (isWindows() && strlen($path) > 260) {
            $path = '\\\\?\\' . str_replace('/', '\\', $path);
        }
        return opendir($path);
    }
}

// Surcharge des fonctions si nécessaire
if (isWindows()) {
    // Redéfinir realpath pour une meilleure compatibilité
    function realpath($path) {
        return realpath_windows($path);
    }
}
?>