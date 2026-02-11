# Rapport d'Audit — ePark Application

Date : 11-02-2026  
Stack : Laravel 12 · Tailwind CSS · Alpine.js · Vite · PHPUnit  
Scope : Code, Architecture, UI/UX, Securite, Tests

---

## 1. Inventaire

### 1.1 Routes (49)

- Publiques (2)
  - GET /
  - GET /mentions-legales
- Guest (8)
  - register (GET/POST)
  - login (GET/POST)
  - forgot-password (GET/POST)
  - reset-password (GET/POST)
- Auth (7)
  - verify-email (GET)
  - verify-email/{id}/{hash} (GET)
  - email/verification-notification (POST)
  - confirm-password (GET/POST)
  - password (PUT)
  - logout (POST)
- Auth + onboarded (30)
  - dashboard
  - admin, admin/stats
  - places (index/create/store/edit/update)
  - disponibilites + exceptions
  - reservations (index/create/store/show/payer/valider/refuser/destroy/feedback)
  - notifications (index/mark-all-read)
  - sites (index/create/store)
  - profile (edit/update/destroy)

### 1.2 Controleurs (11)

- AdminController, AdminStatsController
- DashboardController
- FeedbackController
- OnboardingController
- PlaceAvailabilityController
- PlaceController
- ProfileController
- ReservationController
- SiteController

### 1.3 Modeles (10)

- User, Site, Place, Reservation, Payment, Feedback
- PlaceAvailability, PlaceUnavailability
- AuditLog, Notification

### 1.4 Services (2)

- PlaceAvailabilityService
- ReservationService

### 1.5 Middleware (2)

- EnsureOnboarded
- RoleMiddleware

### 1.6 Policy (1)

- ReservationPolicy

### 1.7 Vues Blade (52)

- layouts, components, auth, errors
- admin (dashboard, stats)
- places, reservations, sites, profile, onboarding, notifications

### 1.8 Migrations (25)

- Users, sites, places, reservations, payments, feedbacks
- Notifications (Laravel + legacy), audit logs
- Availability/unavailability + evolutions de schema

---

## 2. Analyse architecturale

### Points forts

- Separation services/controleurs (ReservationService, PlaceAvailabilityService).
- Policies pour les actions sensibles sur Reservation.
- Gestion des chevauchements fiable (SQLite + MySQL) dans Reservation::overlaps().
- Disponibilites avancees par jour + exceptions.
- Back-office admin + page stats dediee.

### Points faibles

- RoleMiddleware defini mais pas utilise sur les routes admin.
- Log d'audit non alimente (AuditLog existe mais jamais rempli).
- Notifications dupliques (table legacy + table Laravel).
- Calcul du taux d'occupation admin lourd (boucles par place/jour, pas de cache).

---

## 3. Analyse UI/UX

### Incoherences

- Palette et composants heterogenes (indigo/emerald/gray selon les vues).
- Logo Laravel encore present dans application-logo.
- Navigation non adaptee au role (locataire voit places/sites).
- Donnees hardcodees sur la page d'accueil.

### Accessibilite

- Modales custom sans attributs aria.
- Contrastes faibles sur certains textes secondaires.

---

## 4. Analyse fonctionnelle & bugs

### Bugs/risques

1. Place::isAvailableFor(): logique probablement inversee (availabilities traitees comme blocages). A valider.
2. Cache dashboard 5 min sans invalidation lors des changements.
3. Admin (/admin, /admin/stats) protege par check inline, pas de middleware role.

### Fonctionnalites manquantes

- Suppression de place (idealement soft delete).
- Annulation/modification de reservation cote locataire avec delai.
- Export (CSV) pour admin/proprietaires.
- Pagination sur listes (places/reservations).

---

## 5. Analyse securite

- Routes admin non proteges par RoleMiddleware (risque d'erreur si check inline saute).
- Pas de rate limiting sur creation reservation, paiement, feedback.
- CSRF OK (Breeze).
- Hash mots de passe OK (casts).

---

## 6. Tests

### Etat actuel

- Factories presentes : User, Site, Place, Reservation, Payment, Feedback, Availability, Unavailability.
- Tests unitaires : models, services, policy, middleware, helpers, overlap.
- Tests feature : controllers principaux + auth Breeze.

Couverture non mesuree, mais base de tests solide a maintenir.

---

## 7. Recommandations prioritaires

### Priorite haute

1. Appliquer RoleMiddleware sur /admin et /admin/stats.
2. Clarifier la logique des disponibilites (Place::isAvailableFor).
3. Ajouter un mecanisme d'audit (observer ou middleware).

### Priorite moyenne

4. Pagination + filtres sur listes.
5. Rate limiting sur actions sensibles.
6. Cache pour stats admin (surtout taux d'occupation).

### Priorite basse

7. Uniformiser palette et composants UI.
8. Remplacer le logo application.
9. Accessibilite (aria + contrastes).

---

Fin du rapport d'audit.
