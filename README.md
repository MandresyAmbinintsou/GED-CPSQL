#  Système d'Archivage Numérique Optimisé

[![C](https://img.shields.io/badge/C-00599C?style=for-the-badge&logo=c&logoColor=white)](https://en.wikipedia.org/wiki/C_(programming_language))
[![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-316192?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://www.javascript.com/)
[![Linux](https://img.shields.io/badge/Linux-FCC624?style=for-the-badge&logo=linux&logoColor=black)](https://www.linux.org/)
[![Windows](https://img.shields.io/badge/Windows-0078D4?style=for-the-badge&logo=windows&logoColor=white)](https://www.microsoft.com/windows/)

> Solution ultra-rapide de gestion d'archives : scanner des dossiers volumineux, indexer dans PostgreSQL et visualiser via interface web avec conversion PDF dynamique.

##  Fonctionnalités

- ** Scanner C Multi-thread** : Indexation massive jusqu'à 50,000 documents/seconde
- ** Explorateur Web** : Parcourir et choisir n'importe quel dossier du serveur
- ** Recherche Instantanée** : Recherche par matricule, nom ou prénom
- ** Conversion PDF à la volée** : Transformation automatique des images (PNG/JPG) en PDF
- ** Actualisation Automatique** : F5 rafraîchit et rescanner automatiquement
- ** Cross-platform** : Fonctionne sur Linux et Windows (avec XAMPP ou portable)

##  Installation & Configuration

### Prérequis Système
- **OS** : Linux ou Windows
- **Base de données** : PostgreSQL 13+
- **Serveur Web** : Apache/Nginx/IIS avec PHP 8.x
- **Extensions PHP** : `pdo_pgsql`, `pgsql`, `gd`
- **Compilateur** : GCC (Linux) ou MinGW (Windows) pour le scanner C

### Compilation du Scanner (Linux)
```bash
# Compiler le moteur d'indexation haute performance
gcc -o scanner scanner.c -lpq -lpthread -lssl -lcrypto -O3
```

### Configuration Base de Données
Modifier `config/database.php` :
```php
'pgsql:host=localhost;dbname=archives_db;port=5432',
'postgres',  // utilisateur
'postgres'   // mot de passe
```

##  Démarrage Rapide

### Linux (Ubuntu/Debian)
```bash
# Installer les dépendances
sudo apt-get install php php-pgsql postgresql gcc libpq-dev

# Démarrer PostgreSQL et créer la DB
sudo service postgresql start
psql -U postgres -c "CREATE DATABASE archives_db;"

# Lancer l'application
php -S localhost:8000
```

### Windows avec XAMPP
1. Télécharger XAMPP depuis [apachefriends.org](https://www.apachefriends.org/)
2. Placer le projet dans `C:\xampp\htdocs\archive\`
3. Installer PostgreSQL et créer la base `archives_db`
4. Activer `pdo_pgsql` dans `php.ini`
5. Démarrer Apache via le panneau de contrôle XAMPP

### Windows Portable (RECOMMANDÉ) ⚡
```batch
# Double-cliquer sur run_windows.bat
# Structure requise :
# ├── pgsql/          # PostgreSQL portable
# ├── php/            # PHP 8.x portable
# ├── run_windows.bat # Script de démarrage
# └── archive/        # Votre projet
```

> Lors de la première connexion, accéder à `setup.php` pour créer le premier compte administrateur.
> Les utilisateurs peuvent créer leur compte via `formulaire.php` depuis la page de connexion.
> Comptes par défaut : `admin/admin123` (admin) et `user/user123` (utilisateur).

## Utilisation

###  Recherche de Documents
- **Page d'accueil** (`index.php`) : Barre de recherche intelligente
- **Suggestions automatiques** : Matricule, nom, prénom
- **Grille des matricules** : Vue d'ensemble rapide
- **Navigation** : Matricule → Type dossier → Documents

###  Administration & Scan
- **Page Admin** (`admin.php`) : Interface d'administration
- **Parcourir** : Explorateur de dossiers intégré
- **Scan automatique** : F5 lance un nouveau scan
- **Statut temps réel** : Barre de progression et compteurs

###  Gestion des Comptes
- **Configuration initiale** (`setup.php`) : Création du premier compte admin
- **Création de compte** (`formulaire.php`) : Formulaire d'inscription utilisateur
- **Gestion comptes** (`gestion_compte.php`) : Administration des utilisateurs (admin uniquement)
- **Authentification** (`login.php`) : Connexion avec rôles (admin/user)

###  Actualisation Automatique
- **F5 sur index.php** : Rafraîchit et rescanner automatiquement
- **Persistance** : Dernier dossier scanné sauvegardé
- **Synchronisation** : Scan immédiat sans attendre

##  Structure du Projet

```
📦 archive/
├── 📄 scanner.c           # Moteur d'indexation C haute performance
├── 📄 admin.php           # Interface administration & explorateur
├── 📄 index.php           # Interface recherche utilisateur
├── 📄 setup.php           # Configuration initiale des comptes
├── 📄 gestion_compte.php  # Gestion des comptes utilisateurs (admin)
├── 📄 formulaire.php      # Formulaire de création de compte
├── 📄 pdf.php             # Générateur PDF dynamique
├── 📄 login.php           # Authentification
├── 📄 logout.php          # Déconnexion
├── 📁 api/                # API REST JSON
│   ├── 📄 scan-folder.php
│   ├── 📄 list-directories.php
│   └── 📄 ...
├── 📁 config/             # Configuration
│   └── 📄 database.php
├── 📁 templates/          # Templates HTML
├── 📁 archives/           # Données d'exemple
├── 📁 cache_pdf/          # Cache des PDF générés
└── 📄 run_windows.bat     # Script démarrage Windows
```

##  Sécurité

-  **Validation des chemins** : Accès contrôlé aux dossiers
-  **Requêtes préparées** : Protection contre les injections SQL
-  **Gestion d'erreurs** : Pas d'exposition de données sensibles
-  **Authentification** : Système de login/logout
-  **Cache intelligent** : Pas de stockage des données sensibles

##  Performance

- **Scanner C** : 50,000 docs/sec en multithread
- **Indexation optimisée** : Tables partitionnées PostgreSQL
- **Cache PDF** : Génération à la première consultation
- **Recherche instantanée** : Index full-text sur tous les champs
- **Fallback PHP** : Scanner alternatif si binaire C indisponible

##  Support & Contact

**Développeur** : Mandresy Ambinintsou

[![GitHub](https://img.shields.io/badge/GitHub-100000?style=for-the-badge&logo=github&logoColor=white)](https://github.com/MandresyAmbinintsou)

---

##  Notes Importantes

- **Première exécution** : Le scanner crée automatiquement les tables nécessaires
- **Permissions** : Assurer les droits d'écriture sur `cache_pdf/` et `backups/`
- **Extensions PHP** : Vérifier que `pdo_pgsql` est activé
- **PostgreSQL** : Version 13+ recommandée pour les meilleures performances

> [!TIP]
> Pour les environnements de production, utiliser un serveur web dédié (Apache/Nginx) plutôt que le serveur intégré PHP.

---

