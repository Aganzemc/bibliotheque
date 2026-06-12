<?php
$host = getenv('DB_HOST') ?: getenv('SUPABASE_DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: getenv('SUPABASE_DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: getenv('SUPABASE_DB_NAME') ?: 'postgres';
$username = getenv('DB_USER') ?: getenv('SUPABASE_DB_USER') ?: 'postgres';
$password = getenv('DB_PASSWORD') ?: getenv('SUPABASE_DB_PASSWORD') ?: '';
$sslmode = getenv('DB_SSLMODE') ?: getenv('SUPABASE_DB_SSLMODE') ?: 'require';

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
