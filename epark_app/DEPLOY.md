# Publication ePark sur Hostinger

Depuis `epark_app`, exécuter `./deploy-full.ps1`. Le script compile Vite, vérifie les tests puis appelle `scripts/deploy-release.ps1`. `-SkipUpload` prépare uniquement l’archive.

Le déploiement utilise la connexion SSH existante `athys.ch` et la clé locale `athys_hosting_ed25519`. Les mots de passe ne sont jamais passés en arguments.

## Structure du serveur

- Application privée : `/home/u871035213/domains/athys.ch/epark-releases/<version>`.
- Configuration, fichiers et sauvegardes privés : `epark-shared` au même niveau.
- Version active : lien `epark-current`.
- Seul le contenu de `public/` est exposé dans `public_html/epark`.
- La configuration initiale provient de `.env.production`. Une configuration partagée existante n’est jamais écrasée. Ne jamais régénérer sa clé `APP_KEY`.

Les archives excluent la configuration locale, SQLite, les fixtures, les caches, les journaux et les dépendances de développement. Composer installe les dépendances de production sur Linux à partir de `composer.lock`. La base MySQL existante est sauvegardée avant les migrations ; aucun seeder n’est lancé. Les anciennes versions et les sauvegardes restent disponibles hors de la racine web.

Le planificateur Laravel doit être exécuté chaque minute par une entrée cron dédiée à ePark. `scripts/install-scheduler.sh` conserve les autres tâches et évite les doublons. Les journaux se trouvent dans le stockage privé partagé.

Sur l’hébergement vérifié le 24 octobre 2026, `crontab` n’est pas disponible en SSH. Les rappels et les décisions automatiques nécessitent donc une tâche planifiée dans le panneau Hostinger, avec la fréquence `* * * * *` et la commande `/opt/alt/php84/usr/bin/php /home/u871035213/domains/athys.ch/epark-current/artisan schedule:run`. Le script écrit aussi cette commande dans `epark-shared/scheduler-command.txt`. La réservation et les confirmations manuelles fonctionnent sans cette tâche.

`scripts/deploy-release.ps1 -StageOnly` prépare une nouvelle version et sauvegarde la base, sans migrer ni activer. Les identifiants de version doivent être uniques.

## Vérification et retour arrière

Vérifier l’accueil, `/login`, `/express`, `/up`, les assets référencés par le manifeste Vite et les parcours authentifiés dans le navigateur. Les chemins `/.env`, `/composer.json` et `/vendor/autoload.php` doivent être inaccessibles. Tester les données synthétiques sans envoyer de messages aux utilisateurs.

Pour revenir à une version antérieure, rétablir son lien `epark-current` et son dossier public conservé sous `epark-shared/backups/web-<version>`. Ne restaurer une base qu’après avoir évalué les données créées depuis la sauvegarde. Ne jamais supprimer la configuration ou le stockage partagé pour revenir en arrière.

L’application ne possède pas de prestataire de paiement en ligne : les paiements déclarés et remboursements sont un suivi manuel. Ne pas présenter ces écritures comme des mouvements bancaires.
