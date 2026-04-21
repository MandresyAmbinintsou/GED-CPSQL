<?php
// auth.php - Gestion de l'authentification
session_start();
require_once __DIR__ . '/config/database.php';

function ensureUsersTable(PDO $db = null): void {
    if ($db === null) {
        $db = Database::getInstance();
    }

    $db->exec(
        'CREATE TABLE IF NOT EXISTS users ('
        . 'id SERIAL PRIMARY KEY, '
        . 'username TEXT UNIQUE NOT NULL, '
        . 'password_hash TEXT NOT NULL, '
        . 'password_plain TEXT, '
        . 'role TEXT NOT NULL CHECK (role IN (\'admin\', \'user\')), '
        . 'created_at TIMESTAMP NOT NULL DEFAULT NOW()'
        . ')'
    );
    
    try {
        $db->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS password_plain TEXT');
    } catch (Exception $e) {
    }
}

function seedDefaultUsers(PDO $db = null): void {
    if ($db === null) {
        $db = Database::getInstance();
    }

    ensureUsersTable($db);
    $count = (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count === 0) {
        $stmt = $db->prepare('INSERT INTO users (username, password_hash, password_plain, role) VALUES (?, ?, ?, ?)');
        $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin123', 'admin']);
        $stmt->execute(['user', password_hash('user123', PASSWORD_DEFAULT), 'user123', 'user']);
    }
}

function check_auth(): void {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}

function is_admin(): bool {
    return isset($_SESSION['logged_in'], $_SESSION['role'])
        && $_SESSION['logged_in'] === true
        && $_SESSION['role'] === 'admin';
}

function check_admin(): void {
    check_auth();
    if (!is_admin()) {
        header('Location: index.php');
        exit;
    }
}

function hasUsers(PDO $db = null): bool {
    if ($db === null) {
        $db = Database::getInstance();
    }
    ensureUsersTable($db);
    $count = (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    return $count > 0;
}

function createUser(string $username, string $password, string $role): bool {
    try {
        $db = Database::getInstance();
        ensureUsersTable($db);
        $stmt = $db->prepare('INSERT INTO users (username, password_hash, password_plain, role) VALUES (?, ?, ?, ?)');
        return $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $password, $role]);
    } catch (Exception $e) {
        return false;
    }
}

function login(string $username, string $password): bool {
    try {
        $db = Database::getInstance();
        seedDefaultUsers($db);

        $stmt = $db->prepare('SELECT username, password_hash, role FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
    } catch (Exception $e) {
        // Erreur de connexion gérée dans login.php
    }

    return false;
}

function logout(): void {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
