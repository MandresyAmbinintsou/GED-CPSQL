@echo off
REM Script d'installation pour Windows/XAMPP
echo === Installation du système d'archivage pour XAMPP ===

REM Vérifier si XAMPP est installé
if not exist "C:\xampp\php\php.exe" (
    echo ERREUR: XAMPP n'est pas installé dans C:\xampp\
    echo Veuillez installer XAMPP depuis https://www.apachefriends.org/
    pause
    exit /b 1
)

REM Vérifier si Apache et MySQL sont démarrés
echo Vérification des services XAMPP...
net start | find "Apache" >nul
if errorlevel 1 (
    echo Démarrage d'Apache...
    net start apache2.4
)

net start | find "MySQL" >nul
if errorlevel 1 (
    echo Démarrage de MySQL...
    net start mysql
)

REM Attendre que les services soient prêts
timeout /t 3 /nobreak >nul

REM Initialiser la base de données
echo Initialisation de la base de données...
"C:\xampp\php\php.exe" init-database.php

if errorlevel 1 (
    echo ERREUR lors de l'initialisation de la base de données
    pause
    exit /b 1
)

echo.
echo === Installation terminée ===
echo.
echo Pour accéder à l'application :
echo 1. Ouvrez votre navigateur
echo 2. Allez sur : http://localhost/archives/
echo.
echo Identifiants par défaut :
echo - Admin: admin / admin123
echo - User: user / user123
echo.
echo Pour scanner des archives :
echo 1. Créez des dossiers d'employés avec des images
echo 2. Allez dans Administration
echo 3. Cliquez sur "Scanner un dossier"
echo.
pause