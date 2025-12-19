<?php
// Simple migration: copy tables from data.db (SQLite) into MySQL configured in config.php
$config = file_exists(__DIR__ . '/config.php') ? include __DIR__ . '/config.php' : ['use_mysql' => false];
$host = $config['host'] ?? '127.0.0.1';
$port = $config['port'] ?? 3306;
$dbname = $config['db'] ?? 'stokdb';
$user = $config['user'] ?? 'root';
$pass = $config['pass'] ?? '';

try {
    $tmp = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $tmp->exec("CREATE DATABASE IF NOT EXISTS `" . str_replace('`','', $dbname) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $mysql = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    echo "Cannot connect to MySQL: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// create same tables as app expects
$mysql->exec("CREATE TABLE IF NOT EXISTS kategori (id_kategori INT AUTO_INCREMENT PRIMARY KEY, nama_kategori VARCHAR(50) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$mysql->exec("CREATE TABLE IF NOT EXISTS satuan (id_satuan INT AUTO_INCREMENT PRIMARY KEY, nama_satuan VARCHAR(30) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$mysql->exec("CREATE TABLE IF NOT EXISTS barang (id_barang INT AUTO_INCREMENT PRIMARY KEY, nama_barang VARCHAR(100) NOT NULL, id_kategori INT DEFAULT NULL, id_satuan INT DEFAULT NULL, stok INT DEFAULT 0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$mysql->exec("CREATE TABLE IF NOT EXISTS stok_masuk (id_masuk INT AUTO_INCREMENT PRIMARY KEY, id_barang INT, jumlah INT NOT NULL, tanggal DATETIME DEFAULT CURRENT_TIMESTAMP, note TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$mysql->exec("CREATE TABLE IF NOT EXISTS stok_keluar (id_keluar INT AUTO_INCREMENT PRIMARY KEY, id_barang INT, jumlah INT NOT NULL, tanggal DATETIME DEFAULT CURRENT_TIMESTAMP, note TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// connect to sqlite
$sqlite_file = __DIR__ . '/data.db';
if (!file_exists($sqlite_file)) {
    echo "SQLite file not found: {$sqlite_file}\n";
    exit(1);
}
$sqlite = new PDO('sqlite:' . $sqlite_file);
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// disable FK checks for import
$mysql->exec('SET FOREIGN_KEY_CHECKS=0');

// helper to copy a table (preserve ids if present)
function copy_table($fromDb, $toDb, $table, $cols=null) {
    if ($cols === null) {
        $stmt = $fromDb->query("SELECT * FROM {$table}");
    } else {
        $stmt = $fromDb->query("SELECT {$cols} FROM {$table}");
    }
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) return 0;
    // build insert SQL preserving columns
    $colsArr = array_keys($rows[0]);
    $colList = implode(',', array_map(function($c){return "`$c`";}, $colsArr));
    $place = implode(',', array_fill(0, count($colsArr), '?'));
    $ins = $toDb->prepare("INSERT IGNORE INTO {$table} ({$colList}) VALUES ({$place})");
    $count = 0;
    foreach ($rows as $r) {
        $ins->execute(array_values($r));
        $count++;
    }
    return $count;
}

$copied = 0;
foreach (['kategori','satuan','barang','stok_masuk','stok_keluar'] as $t) {
    try {
        $n = copy_table($sqlite, $mysql, $t);
        echo "Copied {$n} rows into {$t}\n";
        $copied += $n;
    } catch (Exception $e) {
        echo "Skipping {$t}: " . $e->getMessage() . "\n";
    }
}

// attempt to add foreign keys after import
try {
  $mysql->exec("ALTER TABLE barang ADD CONSTRAINT fk_barang_kategori FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori)");
  $mysql->exec("ALTER TABLE barang ADD CONSTRAINT fk_barang_satuan FOREIGN KEY (id_satuan) REFERENCES satuan(id_satuan)");
  $mysql->exec("ALTER TABLE stok_masuk ADD CONSTRAINT fk_stok_masuk_barang FOREIGN KEY (id_barang) REFERENCES barang(id_barang)");
  $mysql->exec("ALTER TABLE stok_keluar ADD CONSTRAINT fk_stok_keluar_barang FOREIGN KEY (id_barang) REFERENCES barang(id_barang)");
} catch (Exception $e) {
  echo "Could not add FK constraints: " . $e->getMessage() . PHP_EOL;
}
$mysql->exec('SET FOREIGN_KEY_CHECKS=1');

echo "Migration complete. Total rows copied: {$copied}\n";
exit(0);
