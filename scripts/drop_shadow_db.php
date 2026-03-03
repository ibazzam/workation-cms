<?php
// drops all tables in the connected Postgres database's public schema
// Usage: php scripts/drop_shadow_db.php '<DATABASE_URL>'

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/drop_shadow_db.php '<DATABASE_URL>'\n");
    exit(2);
}

$databaseUrl = $argv[1];
$parts = parse_url($databaseUrl);
if ($parts === false) {
    fwrite(STDERR, "Failed to parse DATABASE_URL\n");
    exit(2);
}

$user = $parts['user'] ?? null;
$pass = $parts['pass'] ?? null;
$host = $parts['host'] ?? null;
$port = $parts['port'] ?? 5432;
$dbname = ltrim($parts['path'] ?? '', '/');

// parse query params (e.g., sslmode=require)
$query = [];
if (!empty($parts['query'])) {
    parse_str($parts['query'], $query);
}

$sslmode = $query['sslmode'] ?? null;
$dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
if ($sslmode) {
    $dsn .= ";sslmode={$sslmode}";
}

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to {$host}:{$port}/{$dbname}\n";

    $sql = <<<'SQL'
DO $$
DECLARE r RECORD;
BEGIN
  FOR r IN (SELECT tablename FROM pg_tables WHERE schemaname = 'public') LOOP
    EXECUTE 'DROP TABLE IF EXISTS public.' || quote_ident(r.tablename) || ' CASCADE';
  END LOOP;
END
$$;
SQL;

    $pdo->exec($sql);
    echo "Dropped all tables in schema 'public'.\n";
    exit(0);
} catch (PDOException $e) {
    fwrite(STDERR, "PDO error: " . $e->getMessage() . "\n");
    exit(1);
} catch (Exception $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
