<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? "Archi-C"; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }
        nav {
            background: #2c3e50;
            padding: 15px 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-logo {
            color: white;
            font-weight: bold;
            font-size: 20px;
            text-decoration: none;
        }
        .nav-links {
            display: flex;
            align-items: center;
        }
        .nav-links a {
            color: #bdc3c7;
            text-decoration: none;
            margin-left: 15px;
            font-weight: 500;
            transition: all 0.2s;
            padding: 5px 0;
        }
        .nav-links a:hover {
            color: white;
        }
        .nav-links a.active {
            color: white;
            border-bottom: 2px solid <?php echo $activeColor ?? '#3498db'; ?>;
        }
        
        /* Styles des boutons */
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
            margin-left: 15px;
            font-family: inherit;
        }
        .btn-success { background: #2ecc71; color: white; }
        .btn-success:hover { background: #27ae60; transform: translateY(-1px); }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; transform: translateY(-1px); }
        .btn-outline { background: transparent; border: 1px solid #bdc3c7; color: #bdc3c7; }
        .btn-outline:hover { border-color: white; color: white; }
    </style>
</head>
<body>
    <nav>
        <div class="container nav-content">
            <a href="index.php" class="nav-logo"> GED-MEF</a>
            <div class="nav-links">
                <a href="index.php" class="<?php echo $currentPage === 'index' ? 'active' : ''; ?>">Accueil</a>
                <a href="admin.php" class="<?php echo $currentPage === 'admin' ? 'active' : ''; ?>">Administration</a>
                
                <button onclick="manualBackup()" class="btn btn-success" title="Sauvegarder la base de données">
                    <span></span> Sauvegarde
                </button>

                <?php if (isset($_SESSION['logged_in'])): ?>
                    <button onclick="confirmLogout()" class="btn btn-danger">Déconnexion</button>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <script>
        // 1. Alerte de déconnexion
        function confirmLogout() {
            if (confirm("Êtes-vous sûr de vouloir vous déconnecter ?")) {
                window.location.href = "logout.php";
            }
        }

        // 2. Sauvegarde manuelle
        async function manualBackup() {
            try {
                const response = await fetch('api/backup-db.php');
                const data = await response.json();
                if (data.success) {
                    alert("Sauvegarde réussie : " + data.file);
                } else {
                    alert("Erreur de sauvegarde : " + data.error);
                }
            } catch (error) {
                alert("Erreur réseau lors de la sauvegarde.");
            }
        }

        // 3. Sauvegarde automatique à la fermeture
        window.addEventListener('beforeunload', function (e) {
            // Utilise sendBeacon pour être sûr que la requête part avant la fermeture
            navigator.sendBeacon('api/backup-db.php');
        });

        // 4. Sauvegarde automatique à 15h
        function checkAutoBackupTime() {
            const now = new Date();
            const hour = now.getHours();
            const minute = now.getMinutes();

            // Si il est 15h00 pile (on vérifie une fois par minute)
            if (hour === 15 && minute === 0) {
                console.log("Il est 15h, lancement de la sauvegarde automatique...");
                fetch('api/backup-db.php');
            }
        }
        
        // Vérifier toutes les minutes
        setInterval(checkAutoBackupTime, 60000);
    </script>
    <div class="container">
