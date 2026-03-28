<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/app/Bootstrap/autoload.php';

use App\Config\Database;
use App\Config\Env;

Env::load($root);

$db = Database::connection();
$db->exec('CREATE TABLE IF NOT EXISTS migrations (id SERIAL PRIMARY KEY, filename VARCHAR(255) NOT NULL UNIQUE, applied_at TIMESTAMP NOT NULL DEFAULT NOW())');

$applied = $db->query('SELECT filename FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
$applied = array_map('strval', $applied ?: []);

$migrationDir = $root . '/database/migrations';
$files = glob($migrationDir . '/*.sql') ?: [];
sort($files);

foreach ($files as $file) {
    $filename = basename($file);
    if (in_array($filename, $applied, true)) {
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException(sprintf('Unable to read migration file: %s', $filename));
    }

    $db->beginTransaction();
    try {
        $db->exec($sql);
        $statement = $db->prepare('INSERT INTO migrations (filename) VALUES (:filename)');
        $statement->execute(['filename' => $filename]);
        $db->commit();
        echo "Applied migration: {$filename}\n";
    } catch (Throwable $exception) {
        $db->rollBack();
        throw $exception;
    }
}

echo "Migrations complete.\n";

