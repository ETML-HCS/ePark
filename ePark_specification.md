# Spécification fonctionnelle — ePark (niveau Codes 5.0)

## 1. Objectif
L'application ePark permet la mise en relation entre particuliers pour la réservation de places de parc à l'heure. Elle gère sites, places, disponibilités horaires, réservations (créneaux d'1h), marges de tolérance (10/15/20 min), paiements, notifications et back-office.

## 2. Glossaire
- Site : lieu géographique regroupant des places.
- Place : emplacement unique au sein d'un site.
- Propriétaire : utilisateur proposant des places.
- Locataire : utilisateur réservant une place.
- Créneau horaire : intervalle d'au moins 60 minutes.
- Battement (marge) : 10/15/20 minutes ajoutées en fin de créneau.

## 3. Rôles & permissions
- Utilisateur (locataire) : recherche, réserve, paie, consulte historique.
- Propriétaire : crée site/place, définit disponibilité, valide réservations, reçoit paiements.
- Hybride : cumule les deux précédents.
```markdown
# Spécification fonctionnelle — ePark (mise à jour 11-02-2026)

Cette spécification reprend le contenu fonctionnel principal et intègre les dernières décisions techniques et actions réalisées au dépôt.

## Rappel des objectifs
ePark permet la mise en relation entre particuliers pour la réservation de places de parc à l'heure, avec gestion des disponibilités, réservations, paiements, notifications et back-office.

## Mises à jour récentes (11-02-2026)
- Interface : harmonisation palette et composants (boutons, navigation), contrastes renforcés et aria sur menus.
- Logo : composant `application-logo` remplacé par l'image ePark.
- Place : ajout d'un delai d'annulation configurable (12h ou 24h) par le proprietaire.
- Place : suppression douce (soft delete) bloquee si reservations actives/a venir.
- Reservation : modification possible pour le locataire (en attente, non payee) + annulation selon delai.
- Admin : page stats dediee (`/admin/stats`) avec top places et taux d'occupation.
- Helpers : `format_chf()` reste obligatoire pour l'affichage des montants.

## Points techniques importants
- Stocker les prix en centimes : `hourly_price_cents` (integer) pour éviter problèmes d'arrondi.
- Notifications : exécution de la migration obligatoire sur l'environnement de production pour aligner le schéma (voir section Migrations & Déploiement).
- Helpers : s'assurer que `app/Support/helpers.php` est disponible au bootstrap pour éviter `Call to undefined function`.
- Annulation : delai applique par place (`cancel_deadline_hours`).
- Soft delete : les places utilisees passent par `deleted_at`.

## Actions à exécuter après déploiement (checklist)
- Installer `rsync` sur l'environnement de déploiement (recommandé) ou lancer le script avec `--allow-scp` en fournissant hôte/utilisateur valides.
- Lancer les migrations : `php artisan migrate --force` (ou planifier via CI/CD).
- Nettoyer et reconstruire caches :
  - `php artisan view:clear`
  - `php artisan cache:clear`
  - `php artisan config:clear`
- Redémarrer les workers/queue/processus si présents (Supervisor/systemd).

## Récapitulatif modélisation & règles (extraits)
- Entités principales : `users`, `sites`, `places`, `reservations`, `payments`, `notifications` (Laravel), `app_notifications` (legacy preserved).
- Règle de conflit créneaux : existing.date_debut < new.date_fin AND existing.date_fin > new.date_debut.
- Battements : stockés dans `reservations.battement_minutes` (0 / 10 / 15 / 20).
- Annulation : autorisee avant `date_debut - cancel_deadline_hours`.
- Reservation modifiable si statut en_attente et paiement pending.

## Déploiement & rollbacks (recommandation)
- Build des assets localement: `npm ci && npm run build` (Vite).
- Préparer dossier `_deploy` (scripts actuels le font), transférer via `rsync -az --delete --exclude ...`.
- Post-deploy : `php artisan migrate --force && php artisan cache:clear && php artisan view:clear`.
- Rollback : garder snapshot DB ou utiliser stratégie de migration réversible ; conserver procédure de restauration documentée.

## Tests & critères d'acceptation (prioritaires)
- Test unitaire: règles de conflit des réservations.
- Test d'intégration: webhook paiement + création Payment.
- E2E: parcours réservation complet (recherche → réservation → paiement → confirmation) sur staging.

## Prochaines tâches recommandées
- Exécuter migrations en production (priorité haute).
- Installer rsync sur l'environnement de déploiement ou valider la procédure `--allow-scp`.
- Automatiser post-deploy (migrations + cache clear) si CI/CD autorisé à exécuter ces commandes.

---

_Mis à jour le : 2026-02-11_
