<?php
// DB connector: prefer MySQL (for phpMyAdmin) if enabled in config.php; otherwise fallback to SQLite.
$config = file_exists(__DIR__ . '/config.php') ? include __DIR__ . '/config.php' : ['use_mysql' => false];
$DB_INFO = ['engine' => 'sqlite', 'status' => 'init', 'message' => 'Using sqlite'];
if (!empty($config['use_mysql'])) {
    $host = $config['host'] ?? '127.0.0.1';
    $port = $config['port'] ?? 3306;
    $dbname = $config['db'] ?? 'stokdb';
    $user = $config['user'] ?? 'root';
    $pass = $config['pass'] ?? '';
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    try {
        $db = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        // create tables: kategori, satuan, barang, stok_masuk, stok_keluar
        $db->exec("CREATE TABLE IF NOT EXISTS kategori (id_kategori INT AUTO_INCREMENT PRIMARY KEY, nama_kategori VARCHAR(50) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE IF NOT EXISTS satuan (id_satuan INT AUTO_INCREMENT PRIMARY KEY, nama_satuan VARCHAR(30) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE IF NOT EXISTS barang (id_barang INT AUTO_INCREMENT PRIMARY KEY, nama_barang VARCHAR(100) NOT NULL, id_kategori INT DEFAULT NULL, id_satuan INT DEFAULT NULL, stok INT DEFAULT 0, FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori), FOREIGN KEY (id_satuan) REFERENCES satuan(id_satuan)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE IF NOT EXISTS stok_masuk (id_masuk INT AUTO_INCREMENT PRIMARY KEY, id_barang INT, jumlah INT NOT NULL, tanggal DATETIME DEFAULT CURRENT_TIMESTAMP, note TEXT, FOREIGN KEY (id_barang) REFERENCES barang(id_barang)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE IF NOT EXISTS stok_keluar (id_keluar INT AUTO_INCREMENT PRIMARY KEY, id_barang INT, jumlah INT NOT NULL, tanggal DATETIME DEFAULT CURRENT_TIMESTAMP, note TEXT, FOREIGN KEY (id_barang) REFERENCES barang(id_barang)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $DB_INFO = ['engine' => 'mysql', 'status' => 'connected', 'host' => $host, 'db' => $dbname, 'user' => $user];
        // populate default categories and units if empty
        try {
          $cnt = $db->query("SELECT COUNT(*) FROM kategori")->fetchColumn(); if ($cnt==0){ $db->exec("INSERT INTO kategori (nama_kategori) VALUES ('Makanan'),('Minuman'),('Bumbu'),('Sembako'),('Snack'),('Perawatan Rumah'),('Peralatan Dapur')"); }
          $cnt2 = $db->query("SELECT COUNT(*) FROM satuan")->fetchColumn(); if ($cnt2==0){ $db->exec("INSERT INTO satuan (nama_satuan) VALUES ('pcs'),('box'),('kg'),('g'),('ltr'),('ml'),('pak'),('roll')"); }
        } catch(Exception $e) {}
        // Auto-migrate legacy tables if present: items -> barang, stock_in -> stok_masuk, stock_out -> stok_keluar
        try {
            $has_items = (bool)$db->query("SHOW TABLES LIKE 'items'")->fetch();
            if ($has_items) {
                $db->exec("INSERT IGNORE INTO barang (nama_barang, id_kategori, id_satuan, stok) SELECT name, NULL, NULL, stock FROM items");
            }
            $has_sin = (bool)$db->query("SHOW TABLES LIKE 'stock_in'")->fetch();
            if ($has_sin) {
                $db->exec("INSERT IGNORE INTO stok_masuk (id_barang,jumlah,tanggal,note) SELECT item_id, qty, date, note FROM stock_in");
            }
            $has_sout = (bool)$db->query("SHOW TABLES LIKE 'stock_out'")->fetch();
            if ($has_sout) {
                $db->exec("INSERT IGNORE INTO stok_keluar (id_barang,jumlah,tanggal,note) SELECT item_id, qty, date, note FROM stock_out");
            }
            // drop legacy tables to keep phpMyAdmin clean (only if migration happened)
            try {
                $db->exec("DROP TABLE IF EXISTS stock_in, stock_out, items");
            } catch (Exception $mx) {
                // ignore drop errors
            }
        } catch (Exception $m) {
            // ignore migration errors
        }
        // attempt auto-renumber IDs if there are gaps (careful: this rewrites IDs)
        try {
            $g = $db->query("SELECT COUNT(*) as c, (MAX(id_barang)-MIN(id_barang)+1) as range_ FROM barang")->fetch(PDO::FETCH_ASSOC);
            if ($g && $g['c'] > 0 && $g['c'] != $g['range_']) {
                // renumber by creating new table and moving rows
                $db->beginTransaction();
                if ($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'){
                    $db->exec('SET FOREIGN_KEY_CHECKS=0');
                    $db->exec("CREATE TABLE IF NOT EXISTS barang_new LIKE barang");
                    $db->exec("TRUNCATE TABLE barang_new");
                } else {
                    $db->exec('PRAGMA foreign_keys=OFF');
                    $db->exec("CREATE TABLE IF NOT EXISTS barang_new (id_barang INTEGER PRIMARY KEY AUTOINCREMENT, nama_barang TEXT NOT NULL, id_kategori INTEGER, id_satuan INTEGER, stok INTEGER DEFAULT 0);");
                    $db->exec("DELETE FROM barang_new");
                }
                $rows = $db->query('SELECT * FROM barang ORDER BY id_barang')->fetchAll(PDO::FETCH_ASSOC);
                $ins = $db->prepare('INSERT INTO barang_new (nama_barang,id_kategori,id_satuan,stok) VALUES (?,?,?,?)');
                $map = [];
                foreach($rows as $r){ $ins->execute([$r['nama_barang'],$r['id_kategori'],$r['id_satuan'],$r['stok']]); $map[$r['id_barang']] = $db->lastInsertId(); }
                foreach($map as $old=>$new){ $db->exec("UPDATE stok_masuk SET id_barang = $new WHERE id_barang = $old"); $db->exec("UPDATE stok_keluar SET id_barang = $new WHERE id_barang = $old"); }
                if ($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'){
                    $db->exec("RENAME TABLE barang TO barang_old, barang_new TO barang");
                    // recreate stok_masuk/stok_keluar to ensure FK point to new barang
                    $db->exec("CREATE TABLE IF NOT EXISTS stok_masuk_new LIKE stok_masuk");
                    $db->exec("INSERT INTO stok_masuk_new SELECT * FROM stok_masuk");
                    $db->exec("RENAME TABLE stok_masuk TO stok_masuk_old, stok_masuk_new TO stok_masuk");
                    $db->exec("DROP TABLE IF EXISTS stok_masuk_old");
                    $db->exec("CREATE TABLE IF NOT EXISTS stok_keluar_new LIKE stok_keluar");
                    $db->exec("INSERT INTO stok_keluar_new SELECT * FROM stok_keluar");
                    $db->exec("RENAME TABLE stok_keluar TO stok_keluar_old, stok_keluar_new TO stok_keluar");
                    $db->exec("DROP TABLE IF EXISTS stok_keluar_old");
                    $db->exec("DROP TABLE IF EXISTS barang_old");
                    $db->exec('SET FOREIGN_KEY_CHECKS=1');
                } else {
                    $db->exec("ALTER TABLE barang RENAME TO barang_old");
                    $db->exec("ALTER TABLE barang_new RENAME TO barang");
                    // sqlite: recreate stok_masuk and stok_keluar with correct FK
                    $db->exec("CREATE TABLE IF NOT EXISTS stok_masuk_new (id_masuk INTEGER PRIMARY KEY AUTOINCREMENT, id_barang INTEGER, jumlah INTEGER NOT NULL, tanggal TEXT, note TEXT, FOREIGN KEY (id_barang) REFERENCES barang(id_barang));");
                    $db->exec("INSERT INTO stok_masuk_new (id_masuk,id_barang,jumlah,tanggal,note) SELECT id_masuk,id_barang,jumlah,tanggal,note FROM stok_masuk");
                    $db->exec("DROP TABLE IF EXISTS stok_masuk");
                    $db->exec("ALTER TABLE stok_masuk_new RENAME TO stok_masuk");
                    $db->exec("CREATE TABLE IF NOT EXISTS stok_keluar_new (id_keluar INTEGER PRIMARY KEY AUTOINCREMENT, id_barang INTEGER, jumlah INTEGER NOT NULL, tanggal TEXT, note TEXT, FOREIGN KEY (id_barang) REFERENCES barang(id_barang));");
                    $db->exec("INSERT INTO stok_keluar_new (id_keluar,id_barang,jumlah,tanggal,note) SELECT id_keluar,id_barang,jumlah,tanggal,note FROM stok_keluar");
                    $db->exec("DROP TABLE IF EXISTS stok_keluar");
                    $db->exec("ALTER TABLE stok_keluar_new RENAME TO stok_keluar");
                    $db->exec("DROP TABLE IF EXISTS barang_old");
                    $db->exec('PRAGMA foreign_keys=ON');
                }
                $db->commit();
            }
        } catch(Exception $re) {}
    } catch (PDOException $e) {
        // try to create database if possible
        try {
            $dsn2 = "mysql:host={$host};port={$port};charset=utf8mb4";
            $tmp = new PDO($dsn2, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $tmp->exec("CREATE DATABASE IF NOT EXISTS `" . str_replace('`','', $dbname) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $db = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $db->exec("CREATE TABLE IF NOT EXISTS kategori (id_kategori INT AUTO_INCREMENT PRIMARY KEY, nama_kategori VARCHAR(50) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $db->exec("CREATE TABLE IF NOT EXISTS satuan (id_satuan INT AUTO_INCREMENT PRIMARY KEY, nama_satuan VARCHAR(30) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $db->exec("CREATE TABLE IF NOT EXISTS barang (id_barang INT AUTO_INCREMENT PRIMARY KEY, nama_barang VARCHAR(100) NOT NULL, id_kategori INT DEFAULT NULL, id_satuan INT DEFAULT NULL, stok INT DEFAULT 0, FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori), FOREIGN KEY (id_satuan) REFERENCES satuan(id_satuan)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $db->exec("CREATE TABLE IF NOT EXISTS stok_masuk (id_masuk INT AUTO_INCREMENT PRIMARY KEY, id_barang INT, jumlah INT NOT NULL, tanggal DATETIME DEFAULT CURRENT_TIMESTAMP, note TEXT, FOREIGN KEY (id_barang) REFERENCES barang(id_barang)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $db->exec("CREATE TABLE IF NOT EXISTS stok_keluar (id_keluar INT AUTO_INCREMENT PRIMARY KEY, id_barang INT, jumlah INT NOT NULL, tanggal DATETIME DEFAULT CURRENT_TIMESTAMP, note TEXT, FOREIGN KEY (id_barang) REFERENCES barang(id_barang)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $DB_INFO = ['engine' => 'mysql', 'status' => 'created', 'host' => $host, 'db' => $dbname, 'user' => $user];
        } catch (PDOException $e2) {
            // fallback to sqlite and record the error message
            $db = new PDO('sqlite:' . __DIR__ . '/data.db');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->exec("CREATE TABLE IF NOT EXISTS kategori (id_kategori INTEGER PRIMARY KEY AUTOINCREMENT, nama_kategori TEXT NOT NULL);");
            $db->exec("CREATE TABLE IF NOT EXISTS satuan (id_satuan INTEGER PRIMARY KEY AUTOINCREMENT, nama_satuan TEXT NOT NULL);");
            $db->exec("CREATE TABLE IF NOT EXISTS barang (id_barang INTEGER PRIMARY KEY AUTOINCREMENT, nama_barang TEXT NOT NULL, id_kategori INTEGER, id_satuan INTEGER, stok INTEGER DEFAULT 0);");
            $db->exec("CREATE TABLE IF NOT EXISTS stok_masuk (id_masuk INTEGER PRIMARY KEY AUTOINCREMENT, id_barang INTEGER, jumlah INTEGER NOT NULL, tanggal TEXT, note TEXT);");
            $db->exec("CREATE TABLE IF NOT EXISTS stok_keluar (id_keluar INTEGER PRIMARY KEY AUTOINCREMENT, id_barang INTEGER, jumlah INTEGER NOT NULL, tanggal TEXT, note TEXT);");
            $DB_INFO = ['engine' => 'sqlite', 'status' => 'fallback', 'message' => 'MySQL error: ' . $e2->getMessage()];
            // Reset sqlite autoincrement counters by vacuum (will not reset AUTOINCREMENT sequence in SQLite without DROP)
            $db->exec('VACUUM;');
        }
    }
} else {
    // SQLite fallback
    $db = new PDO('sqlite:' . __DIR__ . '/data.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Create tables if not exist (sqlite)
    $db->exec("CREATE TABLE IF NOT EXISTS kategori (id_kategori INTEGER PRIMARY KEY AUTOINCREMENT, nama_kategori TEXT NOT NULL);");
    $db->exec("CREATE TABLE IF NOT EXISTS satuan (id_satuan INTEGER PRIMARY KEY AUTOINCREMENT, nama_satuan TEXT NOT NULL);");
    $db->exec("CREATE TABLE IF NOT EXISTS barang (id_barang INTEGER PRIMARY KEY AUTOINCREMENT, nama_barang TEXT NOT NULL, id_kategori INTEGER, id_satuan INTEGER, stok INTEGER DEFAULT 0);");
    $db->exec("CREATE TABLE IF NOT EXISTS stok_masuk (id_masuk INTEGER PRIMARY KEY AUTOINCREMENT, id_barang INTEGER, jumlah INTEGER NOT NULL, tanggal TEXT, note TEXT);");
    $db->exec("CREATE TABLE IF NOT EXISTS stok_keluar (id_keluar INTEGER PRIMARY KEY AUTOINCREMENT, id_barang INTEGER, jumlah INTEGER NOT NULL, tanggal TEXT, note TEXT);");
    $DB_INFO = ['engine' => 'sqlite', 'status' => 'connected', 'file' => __DIR__ . '/data.db'];
    // populate defaults if empty
    try {
      $cnt = $db->query("SELECT COUNT(*) FROM kategori")->fetchColumn(); if ($cnt==0){ $db->exec("INSERT INTO kategori (nama_kategori) VALUES ('Makanan'),('Minuman'),('Bumbu'),('Sembako'),('Snack'),('Perawatan Rumah'),('Peralatan Dapur')"); }
      $cnt2 = $db->query("SELECT COUNT(*) FROM satuan")->fetchColumn(); if ($cnt2==0){ $db->exec("INSERT INTO satuan (nama_satuan) VALUES ('pcs'),('box'),('kg'),('g'),('ltr'),('ml'),('pak'),('roll')"); }
    } catch(Exception $e) {}
    // Try to reduce sqlite sequence gaps
    $db->exec('VACUUM;');
}
?>
