<?php
function get_db() {
    $host = getenv('DB_HOST') ?: 'db';
    $port = getenv('DB_PORT') ?: '5432';
    $db = getenv('POSTGRES_DB') ?: 'appdb';
    $user = getenv('POSTGRES_USER') ?: 'user';
    $pass = getenv('POSTGRES_PASSWORD') ?: 'password';
    $dsn = "pgsql:host={$host};port={$port};dbname={$db}";

    $host = '34.58.189.84'; // Your Cloud SQL Public IP
$port = '5432';          // PostgreSQL default port
$dbname = 'postgres'; // Replace with your actual database name
$user = 'postgres';    // Replace with your database username
$password = 'D!rtydangles1869'; // Replace with your database password

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password);

    // Set error mode to exception for better error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit(1);
}

}
