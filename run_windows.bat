@echo off
SETLOCAL
SET PG_PATH=%~dp0pgsql
SET PG_DATA=%~dp0pgdata
SET PHP_PATH=%~dp0php
SET PORT=5432

echo === DEMARRAGE DU SYSTEME D'ARCHIVAGE PORTABLE ===

:: 1. Verifier si la base de donnees est initialisee
if not exist "%PG_DATA%" (
    echo Initialisation de la base de donnees...
    "%PG_PATH%\bin\initdb" -D "%PG_DATA%" -U postgres --auth=trust
    echo host all all 127.0.0.1/32 trust >> "%PG_DATA%\pg_hba.conf"
)

:: 2. Demarrer PostgreSQL localement
echo Lancement de PostgreSQL...
"%PG_PATH%\bin\pg_ctl" -D "%PG_DATA%" -l logfile start

:: 3. Attendre que Postgres soit pret et creer la DB si elle n'existe pas
timeout /t 2 /nobreak > nul
"%PG_PATH%\bin\psql" -U postgres -c "CREATE DATABASE archives_db;" 2>nul

:: 4. Lancer le serveur PHP
echo Lancement du serveur Web sur http://localhost:8000
start "" http://localhost:8000
php -S localhost:8000 -t .

:: Arret propre au moment de la fermeture
echo Arret de PostgreSQL...
"%PG_PATH%\bin\pg_ctl" -D "%PG_DATA%" stop
pause
