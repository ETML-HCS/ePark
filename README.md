# ePark

![ePark](epark_app/public/logo/ePark.png)

![Version](https://img.shields.io/badge/version-v1.0.0-blue?style=for-the-badge&logo=semantic-release&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Tailwind](https://img.shields.io/badge/TailwindCSS-3-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![Alpine](https://img.shields.io/badge/Alpine.js-3-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=white)
![Tests](https://img.shields.io/badge/tests-passing-2EA043?style=for-the-badge&logo=checkmarx&logoColor=white)
![Status](https://img.shields.io/badge/status-stable-2EA043?style=for-the-badge&logo=github&logoColor=white)
![License](https://img.shields.io/badge/licence-interne-lightgrey?style=for-the-badge&logo=law&logoColor=white)

Plateforme de réservation de places de parking entre particuliers et gestionnaires de sites. ePark simplifie la mise à disposition de places, l’onboarding des utilisateurs et le suivi complet des réservations (paiement, confirmation propriétaire, feedback).

## Objectifs

- Réduire la friction de réservation et de mise à disposition.
- Clarifier le statut des réservations à chaque étape.
- Offrir un parcours utilisateur simple et guidé.

## Parcours utilisateur (référence)

1. Connexion
2. Onboarding simplifié : choisir un site existant OU créer un site favori
3. Réservation : site favori pré‑sélectionné
4. Confirmation propriétaire avec message optionnel
5. Feedback après réservation terminée

## Fonctionnalités clés

- Onboarding en une page avec choix du site favori
- Réservation par date et segment (matin/apm/soir)
- Disponibilités avancées des places + exceptions
- Validation/refus propriétaire avec message
- Paiement et statuts associés
- Feedback à chaud après fin de réservation
- Tableau de bord et indicateurs principaux
- Gestion des sites, places et profils

## Statuts de réservation

- En attente
- Confirmée
- Annulée
- Terminée (confirmée + date de fin passée)

## Rôles

- Locataire : réserve une place
- Propriétaire : propose des places et valide/refuse
- Admin : visibilité globale

## Stack technique

- Laravel 11
- Blade + Alpine.js
- Tailwind CSS
- MySQL/MariaDB

## Démarrage rapide

1. Installer les dépendances PHP et Node
2. Configurer le fichier .env
3. Générer la clé d’application
4. Lancer les migrations et les seeds
5. Démarrer le serveur et le build front

Commandes usuelles (exemples) :
- composer install
- npm install
- php artisan key:generate
- php artisan migrate --seed
- npm run dev
- php artisan serve

## Tests

- php artisan test
- php vendor/bin/phpunit

## Structure fonctionnelle

- Gestion des places : disponibilités, exceptions, plages horaires
- Réservations : création, paiement, validation/refus
- Feedback : note + commentaire après fin

## Notes

- Le statut “Terminée” est calculé côté vue à partir de la date de fin.
- Le site favori est utilisé pour pré‑sélectionner la réservation.

## Licence

Usage interne / projet pédagogique.
