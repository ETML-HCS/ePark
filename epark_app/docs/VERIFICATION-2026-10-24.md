# Vérification du 24 octobre 2026

## Résultat

ePark est publié sur https://epark.athys.ch/. Le parcours de réservation privilégie la date, le créneau complet et le parking, avec sélection des préférences, total visible et demande en un clic quand les choix conviennent. Le bouton reste accessible sur mobile. Le résultat affiche le véritable statut en attente du propriétaire.

Version publique finale : `20261024-195700`, feuille de style `app-B2XoUSZq.css`, JavaScript `app-kiDUh_At.js`. L’entrée PHP pointe explicitement vers cette version pour éviter un ancien chemin de lien symbolique dans le cache PHP de l’hébergement.

La connexion, l’inscription, la navigation, l’activité propriétaire et la liste des réservations ont été simplifiées. Le choix d’un parking est facultatif à l’inscription. La première place proposée active automatiquement le rôle propriétaire. Les réglages de clé personnelle restent dans une section secondaire.

## Corrections vérifiées

- Le total enregistré et le paiement associé correspondent à la durée annoncée, y compris après modification.
- Une heure libre ne suffit plus à annoncer un créneau de plusieurs heures disponible.
- Les créneaux commencés, les places inactives, les groupes privés et les battements sont contrôlés côté serveur.
- La création de réservation et de paiement est transactionnelle, avec verrouillage de la place et rejet du double envoi.
- Un échec d’enregistrement du paiement ne laisse pas une réservation isolée.
- Le mode Express ne simule plus de débit bancaire ni de confirmation propriétaire.
- La déconnexion et l’oubli d’un appareil effacent l’accès Express. Les sessions sont renouvelées à la connexion et les clés ne sont pas sérialisées.
- L’historique conserve les places archivées et les filtres affichent les réservations annulées.
- Les changements de date gèrent les réponses asynchrones et bloquent l’envoi pendant le chargement.

## Preuves

- Laravel : **259 tests réussis, 482 assertions**, `output/tests-final.txt`.
- Vite : compilation réussie.
- Audits : aucune alerte dans `composer audit --no-dev` et `npm audit` après correction des dépendances. Le parseur CSS transitoire est fixé à 7.1.7 ; les déclarations CSS conservées sont identiques à celles de la version précédente.
- Navigateur local : réservation envoyée, statut et prix relus, liste et activité propriétaire ; captures dans `output/playwright/`.
- Navigateur public : connexion réelle avec compte temporaire, écran sans places disponibles, largeur de contenu égale à 390 px sur une fenêtre de 390 px, aucune erreur JavaScript.
- MySQL public : total de 1375 centimes pour 5 h 30 à 250 centimes/h, paiement en attente, validation propriétaire requise, deuxième réservation identique refusée. Les données de ce contrôle ont été annulées dans une transaction.
- Le compte de navigateur temporaire a été supprimé. Contrôle final : zéro utilisateur, site ou place portant les identifiants de vérification de cette passe. Les autres données du serveur ont été conservées.
- HTTP : accueil, connexion, Express et `/up` répondent 200 ; `/.env` répond 403 ; `/composer.json` et `/vendor/autoload.php` répondent 404.

## Publication et limites

Le code applicatif et les sauvegardes sont hors de la racine web. La configuration et le stockage existants sont partagés entre versions. La base a été sauvegardée avant migration, sans exécuter de seeder. Les archives de publication sont dans `output/release/` et sur le serveur privé.

Les règlements restent manuels : aucun prestataire de paiement en ligne n’est configuré. Les e-mails réels n’ont pas été testés par un envoi à un utilisateur.

Le serveur ne fournit pas la commande `crontab`. Les rappels et décisions planifiés nécessitent l’ajout de la tâche décrite dans [DEPLOY.md](../DEPLOY.md) au panneau Hostinger. La réservation et les confirmations manuelles sont opérationnelles.

Le dossier `.git` local ne contient pas de fichier `HEAD` exploitable ; aucun commit n’a été créé. Une archive des sources avant modification est conservée dans `output/release/source-before-20261024.tar.gz`.
