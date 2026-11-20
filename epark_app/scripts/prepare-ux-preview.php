<?php

use App\Models\Place;
use App\Models\Site;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

// Local, isolated browser fixtures. Never run against the application database.
$root = dirname(__DIR__);
$database = $root.'/output/ux-preview.sqlite';
if (! is_dir($root.'/output')) {
    mkdir($root.'/output', 0777, true);
}
touch($database);
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE='.$database);
putenv('MAIL_MAILER=array');
putenv('APP_ENV=local');
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.connections.sqlite.database') !== $database) {
    throw new RuntimeException('Preview database guard failed.');
}
Artisan::call('migrate:fresh', ['--force' => true]);
$owner = User::factory()->create(['name' => 'Camille Propriétaire', 'email' => 'owner@example.test', 'password' => 'Preview-2026!', 'role' => 'proprietaire', 'onboarded' => true]);
$site = Site::factory()->create(['nom' => 'Parking de la Gare', 'adresse' => 'Place de la Gare 1, Lausanne', 'user_id' => $owner->id]);
$otherSite = Site::factory()->create(['nom' => 'Parking du Centre', 'adresse' => 'Rue Centrale 12, Lausanne', 'user_id' => $owner->id]);
$tenant = User::factory()->create(['name' => 'Alex Martin', 'email' => 'tenant@example.test', 'password' => 'Preview-2026!', 'role' => 'locataire', 'onboarded' => true, 'favorite_site_id' => $site->id]);
User::factory()->create(['name' => 'Nouveau membre', 'email' => 'empty@example.test', 'password' => 'Preview-2026!', 'role' => 'locataire', 'onboarded' => false]);
foreach ([['A-12', $site->id, 250], ['B-03', $site->id, 300], ['C-08', $otherSite->id, 200]] as [$name, $siteId, $price]) {
    Place::factory()->create(['nom' => $name, 'user_id' => $owner->id, 'site_id' => $siteId, 'hourly_price_cents' => $price, 'is_active' => true]);
}
echo "Isolated preview ready. Accounts: tenant@example.test, owner@example.test, empty@example.test\n";
