<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

// CLI-only release operations. Kept outside the public document root.
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$mode = $argv[1] ?? 'preflight';
$connection = DB::connection();
if ($mode === 'preflight') {
    $tables = $connection->getSchemaBuilder()->getTableListing();
    echo json_encode(['database_connected' => true, 'driver' => $connection->getDriverName(), 'tables' => count($tables), 'users' => in_array('users', $tables) ? $connection->table('users')->count() : 0, 'reservations' => in_array('reservations', $tables) ? $connection->table('reservations')->count() : 0], JSON_PRETTY_PRINT)."\n";
} elseif ($mode === 'backup') {
    $directory = dirname(__DIR__, 2).'/epark-shared/backups';
    if (! is_dir($directory)) {
        mkdir($directory, 0700, true);
    }
    $configuration = $connection->getConfig();
    if ($connection->getDriverName() !== 'mysql') {
        throw new RuntimeException('Expected the existing MySQL database.');
    }
    $options = tempnam($directory, 'mysql-');
    chmod($options, 0600);
    $quote = fn ($value) => '"'.str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], (string) $value).'"';
    file_put_contents($options, "[client]\nuser=".$quote($configuration['username'])."\npassword=".$quote($configuration['password'])."\nhost=".$quote($configuration['host'])."\nport=".(int) ($configuration['port'] ?? 3306)."\n");
    $target = $directory.'/database-'.date('Ymd-His').'.sql';
    try {
        $process = new Process(['mysqldump', '--defaults-extra-file='.$options, '--single-transaction', '--skip-lock-tables', '--no-tablespaces', '--result-file='.$target, $configuration['database']]);
        $process->setTimeout(240);
        $process->mustRun();
        chmod($target, 0600);
        echo 'Database backed up: '.basename($target).' ('.filesize($target)." bytes)\n";
    } finally {
        unlink($options);
    }
} else {
    throw new RuntimeException('Unknown release operation.');
}
