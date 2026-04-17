#!/bin/bash
# scan_once.sh - À exécuter UNE SEULE FOIS ou manuellement

echo "=== PREMIER ET UNIQUE SCAN DES ARCHIVES ==="
echo "Cela peut prendre plusieurs heures pour des millions de fichiers"
echo "Mais après, PHP répondra en millisecondes !"
echo ""

# Compilation
gcc -o scanner scanner.c -lpq -lpthread -lssl -lcrypto -O3

# Exécution avec le dossier archives
./scanner "archives"

echo ""
echo "=== SCAN TERMINÉ ==="
echo "La base PostgreSQL contient désormais TOUTES les archives"
echo "PHP peut maintenant interroger la base instantanément"