<?php
// config/database.php
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        // Configuration portable : utilise localhost par défaut, mais permet l'override
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '5432';
        $dbname = getenv('DB_NAME') ?: 'archives_db';
        $user = getenv('DB_USER') ?: 'postgres';
        $pass = getenv('DB_PASS') ?: 'postgres';

        try {
            $this->pdo = new PDO(
                "pgsql:host=$host;dbname=$dbname;port=$port",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 5 // Timeout court pour la portabilité
                ]
            );
        } catch (PDOException $e) {
            // ... rest of error handling
            die("Erreur de connexion à la base de données : " . $e->getMessage() . "<br>Assurez-vous que PostgreSQL est lancé et que la base 'archives_db' existe.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->pdo;
    }
}
?>
