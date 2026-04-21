# Restructuration Projet - Déplacer vers api/

## Plan Approuvé
- ✅ Créer TODO.md (cette étape)
- ☐ 1. Déplacer pdf.php → api/pdf.php
- ☐ 2. Mettre à jour les références dans index.php (changer pdf.php → api/pdf.php)
- ☐ 3. Vérifier/tester les changements (liens PDF dans dashboard)
- ☐ 4. Marquer complet et nettoyer TODO.md

## Notes
- Focus: Déplacer pdf.php seulement (backend PDF gen)
- Pas de conversion UI→API pour formulaire/gestion_compte (HTML forms restent root)
- Includes/requires dans pdf.php utilisent déjà paths relatifs corrects (config/database.php, api/scan_helpers.php)

Prochaine étape après confirmation: move file.

