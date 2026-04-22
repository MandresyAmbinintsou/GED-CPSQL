<?php
// test-session.php - Test des sessions PHP
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['test'] = 'Session fonctionne!';
    $_SESSION['timestamp'] = time();
    echo '<h2>✓ Session créée</h2>';
    echo '<p>Valeur: ' . $_SESSION['test'] . '</p>';
    echo '<p>Timestamp: ' . $_SESSION['timestamp'] . '</p>';
    echo '<p><a href="test-session.php">Vérifier la session</a></p>';
} else {
    if (isset($_SESSION['test'])) {
        echo '<h2>✓ Session récupérée</h2>';
        echo '<p>Valeur: ' . $_SESSION['test'] . '</p>';
        echo '<p>Timestamp: ' . $_SESSION['timestamp'] . '</p>';
    } else {
        echo '<h2>✗ Pas de session</h2>';
    }
    echo '<form method="POST"><button>Créer une session</button></form>';
}
?>