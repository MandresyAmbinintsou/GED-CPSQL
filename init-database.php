<?php
// init-database.php - Initialisation automatique de la base de données
require_once 'config/database.php';
require_once 'helpers.php';
require_once 'compat.php';

echo "=== Initialisation de la base de données ===\n";

try {
    $db = Database::getInstance();
    $dbType = $db->getDbType();

    echo "Type de base de données détecté : " . strtoupper($dbType) . "\n";

    // Créer les tables
    echo "Création des tables...\n";

    $db->exec($db->getCreateTableEmployes());
    echo "- Table 'employes' créée\n";

    $db->exec($db->getCreateTableDocuments());
    echo "- Table 'documents' créée\n";

    // Table types_dossiers
    if ($dbType === 'mysql') {
        $db->exec("CREATE TABLE IF NOT EXISTS types_dossiers (
            id INT PRIMARY KEY AUTO_INCREMENT,
            code VARCHAR(50) UNIQUE NOT NULL,
            libelle VARCHAR(200) NOT NULL,
            ordre_affichage INT DEFAULT 0,
            INDEX idx_code (code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } else {
        $db->exec("CREATE TABLE IF NOT EXISTS types_dossiers (
            id SERIAL PRIMARY KEY,
            code VARCHAR(50) UNIQUE NOT NULL,
            libelle VARCHAR(200) NOT NULL,
            ordre_affichage INTEGER DEFAULT 0
        )");
    }
    echo "- Table 'types_dossiers' créée\n";

    // Table scan_history
    if ($dbType === 'mysql') {
        $db->exec("CREATE TABLE IF NOT EXISTS scan_history (
            chemin TEXT PRIMARY KEY,
            dernier_scan TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } else {
        $db->exec("CREATE TABLE IF NOT EXISTS scan_history (
            chemin TEXT PRIMARY KEY,
            dernier_scan TIMESTAMP NOT NULL DEFAULT NOW()
        )");
    }
    echo "- Table 'scan_history' créée\n";

    // Table users pour l'authentification
    if ($dbType === 'mysql') {
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            password_plain TEXT,
            role ENUM('admin', 'user') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } else {
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            password_plain TEXT,
            role TEXT NOT NULL CHECK (role IN ('admin', 'user')),
            created_at TIMESTAMP NOT NULL DEFAULT NOW()
        )");
    }
    echo "- Table 'users' créée\n";

    // Insérer des utilisateurs par défaut
    echo "Insertion des utilisateurs par défaut...\n";
    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    $userHash = password_hash('user123', PASSWORD_DEFAULT);

    try {
        if ($dbType === 'mysql') {
            $db->exec("INSERT IGNORE INTO users (username, password_hash, password_plain, role) VALUES
                ('admin', '$adminHash', 'admin123', 'admin'),
                ('user', '$userHash', 'user123', 'user')");
        } else {
            $db->exec("INSERT INTO users (username, password_hash, password_plain, role) VALUES
                ('admin', '$adminHash', 'admin123', 'admin'),
                ('user', '$userHash', 'user123', 'user')
                ON CONFLICT (username) DO NOTHING");
        }
        echo "- Utilisateurs par défaut insérés\n";
    } catch (Exception $e) {
        echo "- Utilisateurs déjà existants ou erreur : " . $e->getMessage() . "\n";
    }

    // Créer des index pour les performances
    echo "Création des index...\n";
    try {
        if ($dbType === 'mysql') {
            $db->exec("CREATE INDEX IF NOT EXISTS idx_documents_matricule ON documents (matricule)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_documents_type ON documents (type_dossier_nom)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_employes_recherche ON employes (nom, prenom, matricule)");
        } else {
            $db->exec("CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_documents_matricule ON documents (matricule)");
            $db->exec("CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_documents_type ON documents (type_dossier_nom)");
            $db->exec("CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_employes_recherche ON employes USING GIN(to_tsvector('french', nom || ' ' || COALESCE(prenom, '') || ' ' || matricule))");
        }
        echo "- Index créés\n";
    } catch (Exception $e) {
        echo "- Erreur lors de la création des index : " . $e->getMessage() . "\n";
    }

    echo "\n=== Initialisation terminée avec succès ===\n";
    echo "Vous pouvez maintenant utiliser l'application.\n";
    echo "Identifiants par défaut :\n";
    echo "- Admin: admin / admin123\n";
    echo "- User: user / user123\n";

} catch (Exception $e) {
    echo "\nERREUR lors de l'initialisation : " . $e->getMessage() . "\n";
    echo "Assurez-vous que :\n";
    echo "- La base de données existe\n";
    echo "- Les identifiants de connexion sont corrects\n";
    echo "- Le serveur de base de données est démarré\n";
    exit(1);
}
?>