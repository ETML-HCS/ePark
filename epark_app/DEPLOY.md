# Déploiement ePark sur Hostinger

## Commande de déploiement complète

```powershell
.\deploy-full.ps1 -AllowScp -UnzipRemote -IncludeVendor
```

## Options du script

- **`-IncludeVendor`** : Inclut le dossier `vendor/` dans le zip (RECOMMANDÉ pour éviter les problèmes de chemins Composer)
- **`-AllowScp`** : Utilise `scp` au lieu de `rsync` (requis sur Windows sans rsync)
- **`-UnzipRemote`** : Extrait et configure automatiquement sur le serveur
- **`-SkipUpload`** : Crée le zip sans uploader (pour test local)

## Ce que fait le script

1. **Build des assets Vite** : Compile CSS/JS avec `npm run build`
2. **Création du zip** : Package l'application (avec ou sans `vendor/`)
3. **Nettoyage** : Exclut les caches/logs Windows pour éviter les conflits
4. **Upload SSH** : Transfère via `scp` vers `/epark/` sur le serveur
5. **Configuration automatique** :
   - Copie `public/index.php` → `index.php` (racine)
   - Ajuste les chemins (`../vendor` → `vendor`, `../bootstrap` → `bootstrap`)
   - Copie `public/build/` → `build/` (assets Vite accessibles)
   - **Régénère l'autoload Composer** (`composer dump-autoload --optimize`) → **FIX chemins Windows**
   - Supprime les caches de package discovery
   - Définit les permissions (`storage/`, `bootstrap/cache/`)
   - Nettoie tous les caches Laravel (`optimize:clear`, `view:clear`, etc.)
   - Régénère la découverte de packages (`package:discover`)
   - Exécute les migrations (`migrate --force`)
   - Cache la configuration optimisée (`config:cache`)

## Problèmes résolus automatiquement

✅ **Chemins Windows dans Composer** : L'autoload est régénéré sur le serveur  
✅ **Vues compilées avec X:/** : Exclues du zip, régénérées sur serveur  
✅ **CSS/JS non chargés** : Assets Vite copiés de `public/build/` vers `build/`  
✅ **index.php incorrect** : Copié depuis `public/` avec chemins ajustés  
✅ **Variables échappées** : Le script copie le fichier source original  
✅ **Package discovery invalide** : Caches supprimés et régénérés  

## Configuration serveur requise

- **Document root** : `/home/u871035213/domains/athys.ch/public_html/epark/`
- **Structure** : Le document root sert directement `epark/`, PAS `epark/public/`
- **SSH key** : `C:\Users\helde\.ssh\copilot_epark_key`
- **Fichier .env** : `.env.production` (copié vers `.env` sur serveur)

## Commandes manuelles (si nécessaire)

```bash
# Régénérer l'autoload manuellement
composer dump-autoload --optimize

# Nettoyer tous les caches
php artisan optimize:clear
php artisan view:clear
php artisan config:clear

# Régénérer la découverte de packages
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php
php artisan package:discover --ansi

# Copier les assets build
rm -rf build && cp -r public/build .

# Fixer index.php
cp public/index.php index.php
sed -i "s#__DIR__\.\x27/../vendor/autoload.php\x27#__DIR__.\x27/vendor/autoload.php\x27#g" index.php
sed -i "s#__DIR__\.\x27/../bootstrap/app.php\x27#__DIR__.\x27/bootstrap/app.php\x27#g" index.php
```

## Vérification post-déploiement

```bash
# Vérifier que le site répond HTTP 200
curl -I https://epark.athys.ch

# Vérifier les assets CSS
curl -I https://epark.athys.ch/build/assets/app-*.css

# Vérifier les logs d'erreur
tail -n 50 storage/logs/laravel.log
```

## En cas d'erreur HTTP 500

1. Vérifier les logs : `tail storage/logs/laravel.log`
2. Tester l'autoload : `php -r 'require __DIR__."/vendor/autoload.php"; echo "OK\n";'`
3. Tester artisan : `php artisan --version`
4. Régénérer l'autoload : `composer dump-autoload --optimize`
5. Nettoyer les caches : `php artisan optimize:clear`

## Restauration backup

```bash
cd /home/u871035213/domains/athys.ch/public_html
rm -rf epark
tar -xzf ~/backups/epark_backup_YYYYMMDD-HHMMSS.tar.gz
```
