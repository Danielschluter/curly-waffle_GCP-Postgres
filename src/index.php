<?php
require 'db.php';
$pdo = get_db();
try {
    $stmt = $pdo->prepare("-- show all tables and size in megabytes
SELECT
  pg_catalog.pg_tables.tablename AS table_name,
  pg_catalog.pg_size_pretty(pg_catalog.pg_total_relation_size('' || pg_catalog.pg_tables.schemaname || '.' || pg_catalog.pg_tables.tablename || '')) AS size_with_units,
  (pg_catalog.pg_total_relation_size('' || pg_catalog.pg_tables.schemaname || '.' || pg_catalog.pg_tables.tablename || '') / 1024.0 / 1024.0) AS size_mb
FROM
  pg_catalog.pg_tables
WHERE
  pg_catalog.pg_tables.schemaname NOT IN ('pg_catalog',
    'information_schema')
ORDER BY
  size_mb DESC
NULLS LAST;");

$stmt->execute();

// Fetch all rows as associative arrays
$rslt = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rslt as $row) {
    echo json_encode($row) . "<br>";
}


    
} catch (Exception $e) {

}
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>PHP + PostgreSQL</title>
  </head>
  <body>
    <h1>PHP + PostgreSQL</h1>
    <p>Postgres version: <?php echo htmlspecialchars($version); ?></p>
  </body>
</html>
