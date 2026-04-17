C'est noté. Voici une version épurée de ton **README**, sans aucune puce, en utilisant des blocs de texte et une structure visuelle plus linéaire pour un aspect très propre.

-----

#  Système d'Archivage Numérique Optimisé

Ce projet est une solution de gestion d'archives ultra-rapide permettant de scanner des dossiers volumineux, d'indexer les documents dans PostgreSQL et de les visualiser via une interface web avec conversion PDF dynamique.

###  Technologies utilisées

\<p align="left"\>
\<img src="[https://raw.githubusercontent.com/devicons/devicon/master/icons/c/c-original.svg](https://www.google.com/search?q=https://raw.githubusercontent.com/devicons/devicon/master/icons/c/c-original.svg)" alt="C" width="40" height="40"/\>\&nbsp;
\<img src="[https://raw.githubusercontent.com/devicons/devicon/master/icons/php/php-original.svg](https://www.google.com/search?q=https://raw.githubusercontent.com/devicons/devicon/master/icons/php/php-original.svg)" alt="PHP" width="40" height="40"/\>\&nbsp;
\<img src="[https://raw.githubusercontent.com/devicons/devicon/master/icons/postgresql/postgresql-original.svg](https://www.google.com/search?q=https://raw.githubusercontent.com/devicons/devicon/master/icons/postgresql/postgresql-original.svg)" alt="PostgreSQL" width="40" height="40"/\>\&nbsp;
\<img src="[https://raw.githubusercontent.com/devicons/devicon/master/icons/javascript/javascript-original.svg](https://www.google.com/search?q=https://raw.githubusercontent.com/devicons/devicon/master/icons/javascript/javascript-original.svg)" alt="JS" width="40" height="40"/\>\&nbsp;
\<img src="[https://raw.githubusercontent.com/devicons/devicon/master/icons/linux/linux-original.svg](https://www.google.com/search?q=https://raw.githubusercontent.com/devicons/devicon/master/icons/linux/linux-original.svg)" alt="Linux" width="40" height="40"/\>\&nbsp;
\<img src="[https://raw.githubusercontent.com/devicons/devicon/master/icons/windows8/windows8-original.svg](https://www.google.com/search?q=https://raw.githubusercontent.com/devicons/devicon/master/icons/windows8/windows8-original.svg)" alt="Windows" width="40" height="40"/\>
\</p\>

-----

##  Fonctionnalités

Le **Scanner C Multi-thread** assure une indexation massive atteignant 50,000 documents par seconde.
L'**Explorateur Web** permet de parcourir et de choisir n'importe quel dossier du serveur à scanner.
La **Recherche Instantanée** facilite la localisation par matricule, nom ou prénom.
La **Conversion PDF à la volée** transforme les images PNG et JPG en PDF lors de la consultation.

-----

##  Installation et Compilation

### Prérequis

Le système nécessite Linux ou Windows avec un compilateur GCC pour le moteur C.
La base de données doit être PostgreSQL 13 ou une version supérieure.
Le serveur web doit intégrer PHP 8.x avec les extensions pdo\_pgsql et gd activées.

### Compilation du Scanner (Linux)

Le moteur d'indexation se compile avec les optimisations maximales via la commande suivante :

```bash
gcc -o scanner scanner.c -lpq -lpthread -lssl -lcrypto -O3
```

### Configuration

La connexion se définit dans le fichier `config/database.php` en ajustant le DSN, l'utilisateur et le mot de passe de la base de données.

-----

##  Démarrage Rapide

**Sous Linux** : Exécutez la commande `php -S localhost:8000` à la racine du projet.

**Sous Windows** : Utilisez le script `run_windows.bat` pour un lancement automatique de l'environnement portable.

**Accès par défaut** : L'URL est http://localhost:8000 avec les identifiants admin / admin123.

-----

##  Structure du Projet

**scanner.c** contient le code source du moteur d'indexation haute performance.
**admin.php** gère l'interface d'administration et l'explorateur de dossiers.
**index.php** constitue l'interface de recherche pour les utilisateurs.
**pdf.php** s'occupe de la génération dynamique des fichiers PDF.
**api/** regroupe les points d'accès JSON pour les fonctionnalités web.

-----

##  Sécurité

Le système valide systématiquement les chemins pour interdire l'accès aux dossiers sensibles.
Toutes les interactions avec la base de données utilisent des requêtes préparées via PDO.
La gestion d'erreurs est centralisée pour éviter l'exposition de données de débogage.

-----

##  Auteur

**Mandresy Ambinintsou**
Développeur spécialisé en systèmes et performance logicielle.

\<p align="left"\>
\<a href="[https://github.com/MandresyAmbinintsou/](https://github.com/MandresyAmbinintsou/)" target="\_blank"\>
\<img src="[https://img.shields.io/badge/GitHub-100000?style=for-the-badge\&logo=github\&logoColor=white](https://www.google.com/search?q=https://img.shields.io/badge/GitHub-100000%3Fstyle%3Dfor-the-badge%26logo%3Dgithub%26logoColor%3Dwhite)" alt="Github Profile" /\>
\</a\>
\</p\>

> [\!IMPORTANT]  
>  Pour toute utilisation ou contribution, merci de m'ajouté aux contributeurs du dépôt.