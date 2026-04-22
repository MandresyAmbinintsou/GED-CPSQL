<?php
// test-login.php - Test détaillé de la connexion
require_once 'auth.php';

echo '<h2>Test de connexion</h2>';
echo '<hr>';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<p><strong>Tentative de connexion:</strong></p>";
    echo "<p>Username: " . htmlspecialchars($username) . "</p>";
    echo "<p>Password: " . (strlen($password) > 0 ? '[masqué]' : '[vide]') . "</p>";
    
    if (login($username, $password)) {
        echo "<p style='color: green;'><strong>✓ Connexion réussie!</strong></p>";
        echo "<p>Session:" . json_encode($_SESSION) . "</p>";
        echo "<p><a href='index.php'>Aller à l'accueil →</a></p>";
    } else {
        echo "<p style='color: red;'><strong>✗ Connexion échouée</strong></p>";
        echo "<p>Vérifiez vos identifiants</p>";
    }
} else {
    echo '<form method="POST">';
    echo '<p><label>Username: <input type="text" name="username" value="admin"></label></p>';
    echo '<p><label>Password: <input type="password" name="password" value="admin123"></label></p>';
    echo '<p><button>Tester la connexion</button></p>';
    echo '</form>';
}
?>