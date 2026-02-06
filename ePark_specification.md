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
- Administrateur : back-office, modération, reporting.

Principales permissions (extrait) : recherche, réservation (tous), création site/place (propriétaire), back-office (admin).

## 4. Modèle conceptuel (entités principales)
- User(id, nom, prénom, email, password_hash, role, contact, created_at, updated_at)
- Site(id, user_id(owner), nom, adresse, cp, ville, pays, lat, lng, description, photos[], created_at)
- Place(id, site_id, nom, type, longueur_cm, largeur_cm, hauteur_cm, equipements[], tarif_horaire, statut, instructions_acces, photos[], created_at)
- Reservation(id, user_id, place_id, date_debut, date_fin, statut{en_attente,confirmée,annulée,terminée}, montant, battement_min, paiement_effectue, created_at, updated_at)
- Payment(id, reservation_id, montant, status, provider, provider_ref, created_at)
- Notification(id, user_id, type, payload, sent_at, read_at)
- AuditLog(id, user_id, action, entity_type, entity_id, before, after, created_at)

Relations clefs : User 1-N Site, Site 1-N Place, Place 1-N Reservation, Reservation 1-1 Payment.

## 5. Schéma relationnel conseillé (extraits)
- `users` (id, name, email, password, role, phone, created_at)
- `sites` (id, user_id, nom, address_line, postal_code, city, country, lat, lng, meta_json)
- `places` (id, site_id, user_id, name, type, dimensions_json, equipments_json, hourly_price_cents, is_active)
- `reservations` (id, user_id, place_id, date_debut, date_fin, battement_minutes, status, amount_cents, payment_status)
- `payments` (id, reservation_id, amount_cents, provider, provider_status, provider_ref)
- `notifications`, `audit_logs` similaires.

Ajouter index sur `place_id`, `date_debut`, `date_fin` pour requêtes de conflit.

## 6. Gestion des disponibilités & créneaux
- Granularité : 60 minutes. Les utilisateurs peuvent réserver n créneaux consécutifs.
- Disponibilité propriétaire : indiquée par plage journalière (ex : L-V 08:00-18:00) et exceptions (indisponibilités ponctuelles).
- Calcul réservation : on stocke date_debut/date_fin réels (ISO 8601). Les règles métier appliquent battement à la fin (modifie uniquement date_fin logique si accordé).
- Prévention chevauchement : condition SQL/ORM : existing.date_debut < new.date_fin AND existing.date_fin > new.date_debut => conflit.

## 7. Battements (marges)
- Lors de la création, le locataire peut demander 0 / 10 / 15 / 20 minutes.
- Acceptation : automatique si aucun réservoir suivant empiète sur la marge ; sinon rejet et proposition alternative.
- Notifications automatiques aux deux parties en cas d'accord de battement.
- Stocker battement choisi dans `reservations.battement_minutes`.

## 8. Processus de réservation (flux)
- Recherche → filtrage (date/heure/durée/équipements) → affichage créneaux disponibles → choix place + durée + battement → validation résumé → paiement → confirmation.
- Paiement : via provider (Stripe recommended). On crée Payment en status `pending`, on valide la réservation après confirmation du provider.
- Statuts réservation : `en_attente` (paiement ou validation), `confirmée` (paiement ok), `annulée`, `terminée`.

## 9. Règles métier clés (If-Then synthèse)
- If conflit créneau → then refuser et proposer alternatives.
- If propriétaire modifie disponibilité qui impacte réservation confirmée → then bloquer / notifier et proposer remboursement/relocation.
- If annulation >24h → remboursement partiel/total selon politique.
- If dépassement horaire non autorisé et suivant réservé → then alerte et pénalités possibles.
- If dépassement après fin effective (fin + battement) → then appliquer une pénalité par tranche :
  - >= 1h : 40 CHF (FRS)
  - >= 3h : 80 CHF (FRS)
  - >= 1 jour : 120 CHF (FRS)

## 10. Concurrence
- Vérification atomique avant validation : transaction DB + récheck de conflits.
- Optionnel : verrous courts en mémoire (Redis) pour réservation en cours sur la même place pour haute concurrence.

## 11. Notifications & messages
- Types : confirmation, rappel (e.g. 30m avant), alerte dépassement, modification, paiement.
- Canaux : email, SMS, push (mobile) — preferences utilisateur.
- Rappels configurables (ex : 30m / 10m avant début).
- Rappel automatique 15 minutes avant la fin effective pour liberer la place.

## 12. Paiement & commissions
- Intégration provider (Stripe). Capturer et autoriser paiement avant confirmation.
- Calcul commission (ex 10%) stockée sur transaction.
- Reversement : mettre en place ledger interne et schedule de paiement au propriétaire (webhook + cron).
- Conformité PCI : utiliser tokenisation provider.

## 13. Sécurité & conformité
- HTTPS obligatoire, mots de passe hachés (bcrypt/argon2), MFA optionnel.
- RBAC fin par route / policy (Laravel Policies recommandées).
- RGPD : suppression/anonymisation sur demande, export des données utilisateur.
- Audit logging (création/modif/suppression) stocké 12 mois.

## 14. API & Endpoints (exemple REST)
- GET /api/sites?lat=&lon=&q=&filters=...
- POST /api/sites (owner)
- GET /api/places?site_id=...&filters=...
- POST /api/places (owner)
- GET /api/places/{id}/availability?date=YYYY-MM-DD
- POST /api/reservations (user) {place_id,date_debut,date_fin,battement}
- POST /api/payments/webhook (provider)
- GET /api/users/{id}/reservations

Toutes les écritures protégées par auth et policies.

## 15. Tests & critères d'acceptation
- Écrire tests unitaires (modèles, règles de conflit), tests d'intégration (paiement webhook), et E2E (parcours réservation complet).
- Exemples Given/When/Then :
  - Given place libre 10h-12h; When user réserve 10h-11h; Then réservation confirmée et créneau bloqué.
  - Given réservation confirmée et paiement accepté; When propriétaire annule la disponibilité; Then système notifie et propose remboursement.

## 16. Non-fonctionnel (NFR)
- Performance : <2s pour pages clés, montée à 1000 concurrents.
- Disponibilité : 99.9% SLA.
- Sécurité : conformité RGPD, PCI.
- Scalabilité : découpler services (API, workers, webhooks, cron jobs), files d'attente (Redis), stockage objets pour photos.

## 17. Monitoring, logs et audit
- Centralisation logs (ELK/Datadog), métriques (requests, erreurs, taux d'occupation), alerting.
- Dashboard admin pour enquêtes et reports.

## 18. Back-office & modération
- Gestion utilisateurs, content moderation (photos, descriptions), reporting & configuration (commissions, politiques, marges par défaut).

## 19. Roadmap & extensions possibles
- Abonnements récurrents, API publique, intégration contrôle d'accès, support véhicules électriques, programme fidélité.

## 20. Livrables recommandés
- Document de spécification (ce fichier).
- Schéma ER et migrations SQL.
- Contracts API OpenAPI (YAML).
- Suite de tests (unit/integration/e2e).
- Maquettes UI (desktop/mobile).
- Plan de déploiement CI/CD.

---

_Exporté le : 2026-01-28_
