<?php
// config/database.php - Compatible PostgreSQL et MySQL (XAMPP)
class Database {
    private static $instance = null;
    private $pdo;
    private $dbType;

    private function __construct() {
        // Configuration portable : détecte automatiquement PostgreSQL ou MySQL
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '5432'; // 3306 pour MySQL
        $dbname = getenv('DB_NAME') ?: 'archives_db';
        $user = getenv('DB_USER') ?: 'postgres'; // root pour MySQL
        $pass = getenv('DB_PASS') ?: 'postgres'; // '' pour MySQL
        $dbType = getenv('DB_TYPE') ?: 'auto'; // auto, pgsql, mysql

        // Détection automatique du type de base
        if ($dbType === 'auto') {
            // Tester d'abord MySQL (XAMPP), puis PostgreSQL
            $dbType = $this->detectDatabaseType($host, $port, $user, $pass);
        }

        $this->dbType = $dbType;

        try {
            if ($dbType === 'mysql') {
                $port = getenv('DB_PORT') ?: '3306';
                $user = getenv('DB_USER') ?: 'root';
                $pass = getenv('DB_PASS') ?: '';
                $dsn = "mysql:host=$host;dbname=$dbname;port=$port;charset=utf8mb4";
            } else {
                // PostgreSQL par défaut
                $dsn = "pgsql:host=$host;dbname=$dbname;port=$port";
            }

            $this->pdo = new PDO(
                $dsn,
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 5 // Timeout court pour la portabilité
                ]
            );
        } catch (PDOException $e) {
            $dbName = $dbType === 'mysql' ? 'MySQL/MariaDB' : 'PostgreSQL';
            die("Erreur de connexion à la base de données $dbName : " . $e->getMessage() . "<br>Assurez-vous que $dbName est lancé et que la base '$dbname' existe.");
        }
    }

    private function detectDatabaseType($host, $port, $user, $pass) {
        // Tester MySQL d'abord (XAMPP)
        try {
            $pdo = new PDO("mysql:host=$host;port=3306;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_TIMEOUT => 2,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
            ]);
            $pdo = null;
            return 'mysql';
        } catch (Exception $e) {
            // Tester PostgreSQL
            try {
                $pdo = new PDO("pgsql:host=$host;port=5432", $user, $pass, [
                    PDO::ATTR_TIMEOUT => 2,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
                ]);
                $pdo = null;
                return 'pgsql';
            } catch (Exception $e) {
                // Par défaut PostgreSQL
                return 'pgsql';
            }
        }
    }

    public static function getDbType() {
        $instance = self::getInstance();
        return $instance->dbType;
    }

    public function getPdo() {
        return $this->pdo;
    }

    public function __call($name, $arguments) {
        return $this->pdo->{$name}(...$arguments);
    }

    // Méthodes helper pour la compatibilité SQL
    public function getAutoIncrement() {
        return $this->dbType === 'mysql' ? 'AUTO_INCREMENT' : 'SERIAL';
    }

    public function getCaseInsensitiveLike($column, $value) {
        if ($this->dbType === 'mysql') {
            return "UPPER($column) LIKE UPPER($value)";
        }
        return "$column ILIKE $value";
    }

    public function getUpsertSyntax($table, $insertColumns, $updateColumns) {
        if ($this->dbType === 'mysql') {
            $updateParts = [];
            foreach ($updateColumns as $col) {
                $updateParts[] = "$col = VALUES($col)";
            }
            return "INSERT INTO $table (" . implode(', ', $insertColumns) . ") VALUES (" . str_repeat('?,', count($insertColumns) - 1) . "?) ON DUPLICATE KEY UPDATE " . implode(', ', $updateParts);
        }
        // PostgreSQL
        $updateParts = [];
        foreach ($updateColumns as $col) {
            $updateParts[] = "$col = EXCLUDED.$col";
        }
        return "INSERT INTO $table (" . implode(', ', $insertColumns) . ") VALUES (" . str_repeat('?,', count($insertColumns) - 1) . "?) ON CONFLICT (" . implode(', ', array_slice($insertColumns, 0, 1)) . ") DO UPDATE SET " . implode(', ', $updateParts);
    }

    public function getCreateTableEmployes() {
        if ($this->dbType === 'mysql') {
            return "CREATE TABLE IF NOT EXISTS employes (
                matricule VARCHAR(50) PRIMARY KEY,
                nom VARCHAR(100),
                prenom VARCHAR(100),
                actif BOOLEAN DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        }
        return "CREATE TABLE IF NOT EXISTS employes (
            matricule VARCHAR(50) PRIMARY KEY,
            nom VARCHAR(100),
            prenom VARCHAR(100),
            actif BOOLEAN DEFAULT true,
            created_at TIMESTAMP DEFAULT NOW()
        )";
    }

    public function getCreateTableDocuments() {
        $autoInc = $this->getAutoIncrement();
        if ($this->dbType === 'mysql') {
            return "CREATE TABLE IF NOT EXISTS documents (
                id INT PRIMARY KEY $autoInc,
                matricule VARCHAR(50),
                type_dossier_nom VARCHAR(100) NOT NULL,
                nom_fichier VARCHAR(255) NOT NULL,
                chemin_png TEXT NOT NULL,
                taille_bytes BIGINT,
                hash_md5 VARCHAR(32),
                date_scan DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_matricule (matricule),
                INDEX idx_type_dossier (type_dossier_nom)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        }
        return "CREATE TABLE IF NOT EXISTS documents (
            id $autoInc PRIMARY KEY,
            matricule VARCHAR(50) REFERENCES employes(matricule),
            type_dossier_nom VARCHAR(100) NOT NULL,
            nom_fichier VARCHAR(255) NOT NULL,
            chemin_png TEXT NOT NULL,
            taille_bytes BIGINT,
            hash_md5 VARCHAR(32),
            date_scan DATE,
            created_at TIMESTAMP DEFAULT NOW()
        ) PARTITION BY HASH (matricule)";
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
}
?>
