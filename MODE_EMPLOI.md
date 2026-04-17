# 📖 Mode d'Emploi - Système d'Archivage

Ce guide explique comment utiliser les différentes fonctionnalités de votre nouveau système d'archivage.

## 1. Recherche de documents (Page d'accueil)
La page d'accueil (`index.php`) est votre outil principal pour consulter les archives.

*   **Recherche par texte** : Tapez un matricule (ex: `M001`), un nom ou un prénom dans la barre de recherche. Des suggestions apparaîtront au fur et à mesure.
*   **Liste rapide** : En dessous de la barre de recherche, une grille affiche tous les matricules actuellement présents dans la base de données. Cliquez sur l'un d'eux pour voir ses documents.
*   **Consultation** :
    1.  Cliquez sur un matricule pour afficher ses dossiers (Affectation, Formation, etc.).
    2.  Cliquez sur un type de dossier pour voir les fichiers qu'il contient.
    3.  Cliquez sur **"Voir PDF"** pour ouvrir le document. Le système convertira l'image en PDF automatiquement si c'est la première fois.

## 2. Administration et Scan (Page Admin)
La page `admin.php` permet de mettre à jour la base de données avec de nouveaux fichiers.

### Comment scanner un nouveau dossier :
1.  Allez sur la page **Administration**.
2.  Cliquez sur le bouton **"Parcourir..."**.
3.  Une fenêtre s'ouvre : naviguez dans les dossiers du serveur.
4.  Une fois dans le dossier souhaité, cliquez sur le bouton vert **"Choisir"**.
5.  Le chemin s'affiche dans le champ de texte. Cliquez sur **"Lancer le scan"**.
6.  Le scan travaille en arrière-plan. Une barre de statut vous indiquera quand le processus est lancé et combien de documents ont été trouvés à la fin.

## 3. Maintenance (Actions d'administration)
*   **Vider le cache PDF** : Si vous avez un problème d'espace disque ou si des fichiers ont été modifiés, cliquez sur "Vider le cache PDF". Cela supprimera les PDF générés mais gardera les images originales et la base de données intactes. Les PDF seront recréés lors de la prochaine consultation.

## � Lancement de l'application

### Sous Linux
1. **Installer les dépendances (si nécessaire)**:
   ```bash
   sudo apt-get install php php-pgsql php-cli postgresql postgresql-client libpq-dev gcc make
   ```

2. **Compiler le scanner C (optionnel mais recommandé pour les performances)**:
   ```bash
   cd /chemin/vers/archive
   gcc -o scanner scanner.c -lpq -lpthread -lssl -lcrypto -O3
   ```

3. **Démarrer PostgreSQL**:
   ```bash
   sudo service postgresql start
   # ou
   sudo systemctl start postgresql
   ```

4. **Créer la base de données (première fois)**:
   ```bash
   psql -U postgres -c "CREATE DATABASE archives_db;"
   ```

5. **Lancer le serveur web intégré PHP**:
   ```bash
   cd /chemin/vers/archive
   php -S localhost:8000
   ```

6. **Accéder à l'application**:
   - Ouvrir le navigateur et aller à `http://localhost:8000`
   - Se connecter avec les identifiants par défaut :
     - Utilisateur : `admin`
     - Mot de passe : `admin123`

### Sous Windows
1. **Installer les prérequis**:
   - PHP 8.x (avec extension `pdo_pgsql`) depuis [php.net](https://www.php.net/downloads)
   - PostgreSQL depuis [postgresql.org](https://www.postgresql.org/download/windows/)
   - ImageMagick (optionnel, pour la conversion PDF) depuis [imagemagick.org](https://imagemagick.org/script/download.php#windows)

2. **Configurer PostgreSQL**:
   - Lancer PostgreSQL et créer la base `archives_db` via pgAdmin ou en ligne de commande

3. **Compiler le scanner C (optionnel)**:
   - Utiliser WSL (Windows Subsystem for Linux) ou MinGW pour compiler
   - Ou laisser PHP gérer le scan (plus lent mais fonctionnel)

4. **Lancer le serveur web**:
   ```cmd
   cd C:\chemin\vers\archive
   php -S 127.0.0.1:8000
   ```

5. **Accéder à l'application**:
   - Ouvrir le navigateur et aller à `http://127.0.0.1:8000`
   - Se connecter avec :
     - Utilisateur : `admin`
     - Mot de passe : `admin123`

### Sous Windows avec XAMPP
XAMPP est une solution tout-en-un idéale pour Windows. Voici comment l'utiliser :

1. **Installer XAMPP**:
   - Télécharger XAMPP depuis [apachefriends.org](https://www.apachefriends.org/)
   - Installer avec Apache et PHP activés
   - PostgreSQL n'est pas inclus par défaut, il faut l'installer séparément

2. **Installer PostgreSQL**:
   - Télécharger PostgreSQL depuis [postgresql.org](https://www.postgresql.org/download/windows/)
   - Installer et noter le port (par défaut 5432) et le mot de passe `postgres`

3. **Placer le projet dans XAMPP**:
   ```
   C:\xampp\htdocs\archive\
   ```

4. **Créer la base de données**:
   - Ouvrir pgAdmin (livré avec PostgreSQL)
   - Créer une nouvelle base de données nommée `archives_db`

5. **Démarrer XAMPP**:
   - Ouvrir le panneau de contrôle XAMPP
   - Cliquer sur "Start" pour Apache et MySQL (MySQL n'est pas nécessaire, mais on peut le laisser)

6. **Accéder à l'application**:
   - Ouvrir le navigateur et aller à `http://localhost/archive/`
   - Se connecter avec :
     - Utilisateur : `admin`
     - Mot de passe : `admin123`

**Note** : S'assurer que l'extension `pdo_pgsql` est activée dans XAMPP :
   - Aller dans `C:\xampp\php\php.ini`
   - Chercher `;extension=pdo_pgsql` et supprimer le `;` au début
   - Redémarrer Apache

### Sous Windows : Démarrage automatique avec run_windows.bat ⚡ (RECOMMANDÉ)
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
   - Le serveur web se fermera

**Avantages du script** :
- Aucune installation système requise (tout est portable)
- Zéro configuration manuelle
- Parfait pour le déploiement sur d'autres machines
- Démarre PostgreSQL et PHP en une seule commande

## �🛠 Résolution de problèmes
*   **"Aucun résultat"** : Assurez-vous d'avoir lancé un scan au moins une fois depuis la page d'administration.
*   **"Erreur réseau"** : Vérifiez que votre serveur web et la base de données PostgreSQL sont bien démarrés.
*   **PDF lent à charger** : La première ouverture d'un gros document peut prendre quelques secondes le temps de la conversion. Les ouvertures suivantes seront instantanées.
*   **"Scanner non disponible"** : Le système passera automatiquement au scanner PHP. C'est plus lent mais cela fonctionne.
