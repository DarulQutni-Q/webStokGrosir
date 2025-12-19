<?php
try {
    $db = new PDO('sqlite:' . __DIR__ . '/data.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $q = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
    $tables = $q->fetchAll(PDO::FETCH_COLUMN);
    if (!$tables) { echo "NO_TABLES\n"; exit(0); }
    foreach ($tables as $t) {
        try { $c = $db->query("SELECT COUNT(*) FROM \"$t\"")->fetchColumn(); } catch (Exception $e) { $c = 'ERR'; }
        echo $t . ':' . $c . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
}
