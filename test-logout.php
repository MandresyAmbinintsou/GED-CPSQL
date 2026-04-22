<?php
// test-logout.php - Test de la déconnexion
require_once 'auth.php';

echo '<h2>État actuel</h2>';
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    echo '<p style="color: green;"><strong>✓ Vous êtes connecté en tant que: ' . htmlspecialchars($_SESSION['username']) . '</strong></p>';
    echo '<p><a href="logout.php">Se déconnecter →</a></p>';
} else {
    echo '<p style="color: orange;"><strong>⚠ Vous n\'êtes pas connecté</strong></p>';
    echo '<p><a href="login.php">Se connecter →</a></p>';
}
?>