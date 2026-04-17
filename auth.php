<?php
// auth.php - Gestion de l'authentification
session_start();

function check_auth() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}

function login($username, $password) {
    // Vous pouvez changer ces identifiants ici
    $admin_user = "admin";
    $admin_pass = "admin123";

    if ($username === $admin_user && $password === $admin_pass) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
