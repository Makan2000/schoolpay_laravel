<?php
/**
 * sqlite-to-mysql.php
 *
 * Migre les données d'une base SQLite (database/database.sqlite) vers MySQL,
 * table par table. Les types de colonnes sont gérés par MySQL (le script
 * insère uniquement les données, pas le schéma).
 *
 * Prérequis :
 *   - Les migrations MySQL ont déjà été exécutées (php artisan migrate).
 *   - Les extensions PDO sqlite et pdo_mysql sont activées.
 *
 * Usage :
 *   php sqlite-to-mysql.php
 *
 * Variables d'environnement (ou fichier .env à la racine du projet) :
 *   DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
 *   SQLITE_PATH (optionnel, défaut : database/database.sqlite)
 */

// ---------- Chargement du .env ----------
$baseDir = __DIR__;
$envFile = $baseDir . DIRECTORY_SEPARATOR . '.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if (getenv($key) === false) {
            putenv($key . '=' . $value);
        }
    }
}

$sqlitePath = getenv('SQLITE_PATH') ?: ($baseDir . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'database.sqlite');
$mysqlHost = getenv('DB_HOST') ?: '127.0.0.1';
$mysqlPort = getenv('DB_PORT') ?: '3306';
$mysqlDb   = getenv('DB_DATABASE') ?: 'schoolpay';
$mysqlUser = getenv('DB_USERNAME') ?: 'root';
$mysqlPass = getenv('DB_PASSWORD') ?: '';

if (! is_file($sqlitePath)) {
    fwrite(STDERR, "Fichier SQLite introuvable : {$sqlitePath}\n");
    exit(1);
}

// ---------- Connexions ----------
try {
    $sqlite = new PDO('sqlite:' . $sqlitePath);
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    fwrite(STDERR, "Connexion SQLite échouée : " . $e->getMessage() . "\n");
    exit(1);
}

try {
    $mysql = new PDO(
        "mysql:host={$mysqlHost};port={$mysqlPort};dbname={$mysqlDb};charset=utf8mb4",
        $mysqlUser,
        $mysqlPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Throwable $e) {
    fwrite(STDERR, "Connexion MySQL échouée : " . $e->getMessage() . "\n");
    exit(1);
}

// ---------- Liste des tables SQLite ----------
$tables = [];
$stmt = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
foreach ($stmt->fetchAll() as $row) {
    $tables[] = $row['name'];
}

if (empty($tables)) {
    fwrite(STDERR, "Aucune table trouvée dans la base SQLite.\n");
    exit(1);
}

$mysql->exec('SET FOREIGN_KEY_CHECKS = 0');

foreach ($tables as $table) {
    echo "→ Table « {$table} » … ";

    // Colonnes côté SQLite
    $colsStmt = $sqlite->query("PRAGMA table_info(\"{$table}\")");
    $columns = [];
    foreach ($colsStmt->fetchAll() as $col) {
        $columns[] = $col['name'];
    }
    if (empty($columns)) {
        echo "ignorée (aucune colonne)\n";
        continue;
    }

    $colList = implode(', ', array_map(fn ($c) => "`{$c}`", $columns));

    // Vider la table cible
    try {
        $mysql->exec("TRUNCATE TABLE `{$table}`");
    } catch (Throwable $e) {
        // Fallback si TRUNCATE impossible (FK, vues, etc.)
        $mysql->exec("DELETE FROM `{$table}`");
    }

    // Lire les lignes SQLite
    $rows = $sqlite->query("SELECT * FROM \"{$table}\"")->fetchAll();
    if (empty($rows)) {
        echo "0 ligne\n";
        continue;
    }

    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $insertSql = "INSERT INTO `{$table}` ({$colList}) VALUES ({$placeholders})";
    $insertStmt = $mysql->prepare($insertSql);

    $count = 0;
    foreach ($rows as $row) {
        $values = [];
        foreach ($columns as $col) {
            $values[] = $row[$col] ?? null;
        }
        $insertStmt->execute($values);
        $count++;
    }

    echo "{$count} ligne(s) importée(s)\n";
}

$mysql->exec('SET FOREIGN_KEY_CHECKS = 1');

echo "\nMigration terminée : " . count($tables) . " table(s) traitée(s).\n";
echo "Base MySQL « {$mysqlDb} » mise à jour.\n";
