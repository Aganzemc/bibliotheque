<?php
function loadEnvFile($path) {
    if (!is_readable($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

loadEnvFile(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env');

$databaseUrl = getenv('DATABASE_URL') ?: '';

if ($databaseUrl !== '') {
    $db = parse_url($databaseUrl);

    $host = $db['host'] ?? 'db.pbpfafixkltjdsbqnetk.supabase.co';
    $port = $db['port'] ?? '5432';
    $dbname = isset($db['path']) ? ltrim($db['path'], '/') : 'postgres';
    $username = isset($db['user']) ? urldecode($db['user']) : 'postgres';
    $password = isset($db['pass']) ? urldecode($db['pass']) : '';

    parse_str($db['query'] ?? '', $query);
    $sslmode = $query['sslmode'] ?? 'require';
} else {
    $host = getenv('DB_HOST') ?: getenv('SUPABASE_DB_HOST') ?: 'db.pbpfafixkltjdsbqnetk.supabase.co';
    $port = getenv('DB_PORT') ?: getenv('SUPABASE_DB_PORT') ?: '5432';
    $dbname = getenv('DB_NAME') ?: getenv('SUPABASE_DB_NAME') ?: 'postgres';
    $username = getenv('DB_USER') ?: getenv('SUPABASE_DB_USER') ?: 'postgres';
    $password = getenv('DB_PASSWORD') ?: getenv('SUPABASE_DB_PASSWORD') ?: '';
    $sslmode = getenv('DB_SSLMODE') ?: getenv('SUPABASE_DB_SSLMODE') ?: 'require';
}

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=$sslmode";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
