<?php
// setup.php - Configuration initiale du système
require_once 'auth.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Tous les champs sont requis.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } else {
        if (createUser($username, $password, 'admin')) {
            $message = 'Compte administrateur créé avec succès. <a href="login.php">Se connecter</a>';
        } else {
            $error = 'Erreur lors de la création du compte.';
        }
    }
}

// Si des utilisateurs existent déjà, rediriger vers login
if (hasUsers()) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Configuration initiale - GED-MEF</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .setup-card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #2c3e50; margin-bottom: 30px; border-bottom: 2px solid #e74c3c; padding-bottom: 10px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #7f8c8d; }
        input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #3498db; border: none; color: white; border-radius: 5px; cursor: pointer; font-size: 16px; transition: background 0.3s; }
        button:hover { background: #2980b9; }
        .error { color: #e74c3c; text-align: center; margin-bottom: 20px; font-size: 14px; }
        .success { color: #27ae60; text-align: center; margin-bottom: 20px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="setup-card">
        <h2>Configuration initiale</h2>
        <p style="text-align: center; color: #7f8c8d; margin-bottom: 20px;">Aucun compte n'existe. Créez le premier compte administrateur :</p>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Nom d'utilisateur</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Créer le compte admin</button>
        </form>
    </div>
</body>
</html>