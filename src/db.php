<?php
function get_db() {
    $host = '34.58.189.84'; // Your Cloud SQL Public IP
    $port = '5432';
    $db = 'postgres';      // Your actual database name
    $user = 'postgres';    // Your database username
    $password = 'D!rtydangles1869'; // Your database password

    $dsn = "pgsql:host={$host};port={$port};dbname={$db}";

    try {
        $pdo = new PDO($dsn, $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
        exit(1);
    }
}
