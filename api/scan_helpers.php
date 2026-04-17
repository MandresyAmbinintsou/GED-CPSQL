<?php
// api/scan_helpers.php - Fonctions utilitaires pour le scanner et la compatibilité Windows

function isWindows() {
    return strncasecmp(PHP_OS, 'WIN', 3) === 0;
}

function normalizePath($path) {
    return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
}

function normalizeDbPath($path) {
    return str_replace(['\\'], '/', $path);
}

function isAbsolutePath($path) {
    if (!$path) {
        return false;
    }

    if ($path[0] === '/' || $path[0] === '\\') {
        return true;
    }

    return preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
}

function resolveTargetPath($folder_path, $base_dir) {
    $folder_path = trim($folder_path);
    $folder_path = normalizePath($folder_path);

    if (isAbsolutePath($folder_path)) {
        return realpath($folder_path);
    }

    return realpath($base_dir . DIRECTORY_SEPARATOR . $folder_path);
}

function findScannerPath($base_dir) {
    $scanner = $base_dir . DIRECTORY_SEPARATOR . 'scanner';
    if (file_exists($scanner) && is_executable($scanner)) {
        return $scanner;
    }

    if (isWindows()) {
        $scannerExe = $scanner . '.exe';
        if (file_exists($scannerExe) && is_executable($scannerExe)) {
            return $scannerExe;
        }
    }

    return null;
}

function commandExists($command) {
    if (isWindows()) {
        $check = 'where ' . escapeshellarg($command);
    } else {
        $check = 'command -v ' . escapeshellarg($command);
    }

    exec($check . ' 2>NUL', $output, $return_var);
    return $return_var === 0;
}

function runDetachedCommand($command) {
    if (isWindows()) {
        $cmd = 'cmd /c start /B "" ' . $command;
        pclose(popen($cmd, 'r'));
        return true;
    }

    $logFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'scanner.log';
    $cmd = $command . ' > ' . escapeshellarg($logFile) . ' 2>&1 &';
    exec($cmd);
    return true;
}

function isImageFile($filename) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if (!$ext) {
        return false;
    }

    $ext = strtolower($ext);
    return in_array($ext, ['png', 'jpg', 'jpeg', 'tif', 'tiff'], true);
}

function buildScannerCommand($scanner_path, $target_path) {
    return escapeshellarg($scanner_path) . ' ' . escapeshellarg($target_path);
}

function normalizePathForDatabase($path) {
    return normalizeDbPath(realpath($path) ?: $path);
}

?>
