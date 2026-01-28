# Plan du projet Laravel ePark


## 1. Initialisation et configuration du projet
- Initialisation du dépôt Git (`git init`)
- Création du fichier `.gitignore` (exclusion des fichiers/dossiers sensibles et inutiles)
- Création du projet Laravel
- Configuration de la base de données
- Installation de Breeze/Jetstream pour l’authentification

---

## 2. Modélisation des données (migrations & modèles)
- Utilisateur (User) avec gestion des rôles (Locataire, Propriétaire, ou les deux)
- Site (nom, adresse, propriétaire)
- Place (site, disponibilité, caractéristiques)
- Réservation (utilisateur, place, date, statut)

---

## 3. Authentification et gestion des rôles
- Inscription/connexion
- Attribution et gestion des rôles
- Middleware pour sécuriser les routes selon le rôle

---

## 4. Fonctionnalités principales (contrôleurs & vues)
- Tableau de bord selon le rôle
- Réservation d’une place (locataire)
- Ajout de place à un site existant (propriétaire)
- Création d’un nouveau site (propriétaire)
- Gestion des réservations, places, sites

---

## 5. Interface utilisateur (Blade ou API)
- Pages responsives pour mobile (Blade + Bootstrap/Tailwind)
- (Optionnel) API REST pour front mobile séparé

---

## 6. Fonctionnalités avancées (optionnel)
- Carte interactive (Leaflet/Google Maps)
- Notifications (email, in-app)
- Paiement en ligne
