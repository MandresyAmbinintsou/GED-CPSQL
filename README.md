# Système d'Archivage Numérique Optimisé

Ce projet est une solution de gestion d'archives ultra-rapide permettant de scanner des dossiers volumineux, d'indexer les documents dans PostgreSQL et de les visualiser via une interface web avec conversion PDF dynamique.

## 🚀 Fonctionnalités
- **Scanner C Multi-thread** : Indexation massive (jusqu'à 50,000 docs/sec).
- **Explorateur Web** : Parcourir et choisir n'importe quel dossier du serveur à scanner.
- **Recherche Instantanée** : Recherche par matricule, nom ou prénom.
- **Conversion PDF à la volée** : Transformation des images (PNG/JPG) en PDF lors de la consultation.

## 🛠 Installation et Compilation

### 1. Prérequis
- **Système** : Linux ou Windows
- **Outils** : `gcc` (Linux / WSL / MinGW) ou PHP 8 avec un fallback PHP de scan
- **Base de données** : PostgreSQL
- **Serveur Web** : Apache/Nginx/IIS avec PHP 8.x (extensions `pdo_pgsql`, `pgsql`)
- **Conversion PDF** : `img2pdf`, ImageMagick (`magick`) ou extension PHP `Imagick`

### 2. Compilation du Scanner
Le scanner est écrit en C pour des performances maximales. Pour le compiler sous Linux :
```bash
gcc -o scanner scanner.c -lpq -lpthread -lssl -lcrypto -O3
```
Sous Windows, vous pouvez utiliser WSL ou MinGW pour compiler, ou activer le scanner PHP intégré si vous ne disposez pas du binaire C.
*Note : Assurez-vous d'avoir les headers de développement PostgreSQL (`libpq-dev` sous Linux, `postgresql-devel` ou l'équivalent Windows) installés.*
*Pour Windows natif, un port `dirent.h` ou une couche de compatibilité peut être nécessaire pour compiler le scanner C.*

### 3. Configuration de la Base de Données
Modifiez le fichier `config/database.php` pour ajuster vos accès :
```php
'pgsql:host=localhost;dbname=archives_db;port=5432', 'postgres', 'votre_mot_de_passe'
```
*Le scanner créera automatiquement les tables et les partitions lors de son premier lancement.*

## � Démarrage de l'application

### Sous Linux
```bash
# 1. Cloner ou extraire le projet
cd /chemin/vers/archive

# 2. Installer les dépendances (première fois)
sudo apt-get install php php-pgsql php-cli postgresql postgresql-client libpq-dev gcc make img2pdf

# 3. Compiler le scanner (optionnel mais recommandé)
gcc -o scanner scanner.c -lpq -lpthread -lssl -lcrypto -O3

# 4. Démarrer PostgreSQL (si nécessaire)
sudo service postgresql start

# 5. Créer la base de données (première fois)
psql -U postgres -c "CREATE DATABASE archives_db;"

# 6. Lancer l'application
php -S localhost:8000
```

### Sous Windows
```cmd
# 1. Ouvrir l'Invite de commandes et se placer dans le dossier du projet
cd C:\chemin\vers\archive

# 2. Lancer l'application
php -S 127.0.0.1:8000

# 3. Accéder via le navigateur
http://127.0.0.1:8000
```

### Sous Windows avec XAMPP
XAMPP est une solution tout-en-un idéale pour Windows. Voici comment l'utiliser :

1. **Installer XAMPP** :
   - Télécharger depuis [apachefriends.org](https://www.apachefriends.org/)
   - Installer avec Apache et PHP activés
   - PostgreSQL n'est pas inclus par défaut, installer séparément

2. **Installer PostgreSQL** :
   - Télécharger depuis [postgresql.org](https://www.postgresql.org/download/windows/)
   - Installer et noter le port (défaut 5432) et mot de passe `postgres`

3. **Placer le projet dans XAMPP** :
   ```
   C:\xampp\htdocs\archive\
   ```

4. **Créer la base de données** :
   - Ouvrir pgAdmin (inclus avec PostgreSQL)
   - Créer une base `archives_db`

5. **Démarrer XAMPP** :
   - Ouvrir le Panneau de Contrôle XAMPP
   - Cliquer "Start" pour Apache et MySQL

6. **Accéder à l'application** :
   - Ouvrir le navigateur : `http://localhost/archive/`
   - Se connecter : utilisateur `admin`, mot de passe `admin123`

**Note** : Activer l'extension `pdo_pgsql` dans XAMPP :
   - Éditer `C:\xampp\php\php.ini`
   - Trouver `;extension=pdo_pgsql` et supprimer le `;`
   - Redémarrer Apache

### Windows : Démarrage automatique avec run_windows.bat ⚡ (RECOMMANDÉ)
Le fichier `run_windows.bat` lance **tout automatiquement** sans configuration manuelle :

1. **Double-cliquer sur `run_windows.bat`** depuis le dossier du projet.

2. **Le script exécute automatiquement** :
   - ✅ Initialise la base de données PostgreSQL (si première exécution)
   - ✅ Démarre le serveur PostgreSQL localement
   - ✅ Crée la base `archives_db` si elle n'existe pas
   - ✅ Démarre le serveur Web PHP sur `http://localhost:8000`
   - ✅ Ouvre automatiquement le navigateur

3. **Prérequis** :
   - Un dossier `pgsql/` contenant PostgreSQL portable (télécharger depuis [postgresql.org](https://www.postgresql.org/download/windows/))
   - Un dossier `php/` contenant PHP 8.x (télécharger depuis [php.net](https://www.php.net/downloads))
   - Ces dossiers doivent être au même niveau que le projet (voir structure ci-dessous)

**Structure attendue** :
```
C:\Mon Projet\
├── pgsql/              # PostgreSQL portable
├── php/                # PHP 8.x portable
├── run_windows.bat     # Le script de démarrage
├── archive/            # Votre projet (ce dossier)
│   ├── admin.php
│   ├── index.php
│   ├── api/
│   ├── config/
│   └── ...
```

4. **À la fermeture** :
   - Appuyer sur une touche pour arrêter PostgreSQL proprement
   - Le serveur web se ferme

**Avantages** :
- Aucune installation système requise (tout est portable)
- Zéro configuration manuelle
- Parfait pour le déploiement sur d'autres machines
- Démarre PostgreSQL et PHP en une seule commande

### Accès à l'application
- URL : `http://localhost:8000` (Linux) ou `http://127.0.0.1:8000` (Windows CLI) ou `http://localhost/archive/` (XAMPP)
- Utilisateur : `admin`
- Mot de passe : `admin123`

## �📖 Utilisation

### Via l'Interface Web (Recommandé)
1. Accédez à `admin.php`.
2. Cliquez sur le bouton **"Parcourir..."**.
3. Naviguez dans l'arborescence du serveur et cliquez sur **"Choisir"** sur le dossier souhaité.
4. Cliquez sur **"Lancer le scan"** ou appuyez sur **F5** pour un scan synchronisé.

> Sous Windows, si le binaire `scanner.exe` n'est pas présent, l'application utilisera un scanner PHP de secours pour indexer le dossier.

### Via la Ligne de Commande (Linux)
Pour lancer un scan manuel :
```bash
./scanner /chemin/vers/vos/archives
```

### Actualisation automatique
- **Index.php** : Appuyez sur **F5** pour rafraîchir et rescanner le dernier dossier.
- **Admin.php** : Le dernier chemin scanné est restauré au lancement de l'application.

## 📂 Structure du Projet
- `scanner.c` : Code source du moteur d'indexation haute performance.
- `admin.php` : Interface d'administration et explorateur de dossiers.
- `index.php` : Interface de recherche utilisateur.
- `api/` : Points d'accès JSON pour les fonctionnalités web.
- `pdf.php` : Moteur de conversion Image vers PDF.

## 🛡️ Sécurité
- Les chemins sont validés avant tout scan.
- Les erreurs sont capturées et renvoyées au format JSON pour éviter les fuites de debug en production.
- Utilisation de requêtes préparées (PDO) pour prévenir les injections SQL.
